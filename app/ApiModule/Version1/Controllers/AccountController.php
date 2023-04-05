<?php

declare(strict_types = 1);

/**
 * Copyright 2022-2023 Roman Ondráček
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\ApiModule\Version1\Controllers;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\OpenApi;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\RequestParameter;
use Apitte\Core\Annotation\Controller\Tag;
use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Exception\Api\ServerErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\ApiModule\Version1\Models\RestApiSchemaValidator;
use App\ApiModule\Version1\RequestAttributes;
use App\ApiModule\Version1\Utils\BaseUrlHelper;
use App\CoreModule\Models\UserManager;
use App\Exceptions\BlockedAccountException;
use App\Exceptions\ConflictedEmailAddressException;
use App\Exceptions\InvalidAccountStateException;
use App\Exceptions\InvalidEmailAddressException;
use App\Exceptions\InvalidPasswordException;
use App\Exceptions\InvalidUserLanguageException;
use App\Exceptions\ResourceExpiredException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Database\Entities\User;
use App\Models\Database\EntityManager;
use Nette\Mail\SendException;

/**
 * Account controller
 */
#[Path('/account')]
#[Tag('User account')]
class AccountController extends BaseController {

	/**
	 * Constructor
	 * @param EntityManager $entityManager Entity manager
	 * @param UserManager $manager User manager
	 * @param RestApiSchemaValidator $validator REST API JSON schema validator
	 */
	public function __construct(
		private readonly EntityManager $entityManager,
		private readonly UserManager $manager,
		RestApiSchemaValidator $validator,
	) {
		parent::__construct($validator);
	}

	#[Path('/')]
	#[Method('GET')]
	#[OpenApi('
		summary: Gets profile of the logged user
		responses:
			"200":
				description: Success
				content:
					application/json:
						schema:
							$ref: "#/components/schemas/UserDetail"
			"403":
				$ref: "#/components/responses/Forbidden"
	')]
	public function get(ApiRequest $request, ApiResponse $response): ApiResponse {
		$user = $request->getAttribute(RequestAttributes::AppLoggedUser);
		if (!$user instanceof User) {
			throw new ClientErrorException('API key is used', ApiResponse::S403_FORBIDDEN);
		}
		return $response->writeJsonObject($user);
	}


	#[Path('/')]
	#[Method('PUT')]
	#[OpenApi('
		summary: Edits user
		requestBody:
			required: true
			content:
				application/json:
					schema:
						$ref: "#/components/schemas/AccountEdit"
		responses:
			"200":
				description: Success
			"400":
				$ref: "#/components/responses/BadRequest"
			"403":
				$ref: "#/components/responses/Forbidden"
			"409":
				description: Username or e-mail address is already used
	')]
	public function edit(ApiRequest $request, ApiResponse $response): ApiResponse {
		$baseUrl = BaseUrlHelper::get($request);
		$user = $request->getAttribute(RequestAttributes::AppLoggedUser);
		if (!$user instanceof User) {
			throw new ClientErrorException('API key is used', ApiResponse::S403_FORBIDDEN);
		}
		$this->validator->validateRequest('accountEdit', $request);
		$json = $request->getJsonBody();
		try {
			$user->editFromJson($json);
			if (($json['changePassword'] ?? false) === true) {
				$user->changePassword($json['oldPassword'], $json['newPassword']);
			}
			$this->manager->edit($user, $baseUrl);
			return $response->withStatus(ApiResponse::S200_OK);
		} catch (InvalidEmailAddressException | InvalidPasswordException $e) {
			throw new ClientErrorException($e->getMessage(), ApiResponse::S400_BAD_REQUEST);
		} catch (InvalidUserLanguageException) {
			throw new ClientErrorException('Invalid language', ApiResponse::S400_BAD_REQUEST);
		} catch (ConflictedEmailAddressException) {
			throw new ClientErrorException('E-mail address is already used', ApiResponse::S409_CONFLICT);
		}
	}

	#[Path('/verification/resend')]
	#[Method('POST')]
	#[OpenApi('
		summary: Resends the verification e-mail
		responses:
			"200":
				description: Success
			"400":
				description: User is already verified
			"500":
				description: Unable to send the e-mail
	')]
	public function resendVerification(ApiRequest $request, ApiResponse $response): ApiResponse {
		$baseUrl = BaseUrlHelper::get($request);
		$user = $request->getAttribute(RequestAttributes::AppLoggedUser);
		if (!$user instanceof User) {
			throw new ClientErrorException('API key is used', ApiResponse::S403_FORBIDDEN);
		}
		try {
			$this->manager->sendVerificationEmail($user, $baseUrl);
		} catch (SendException $e) {
			throw new ServerErrorException('Unable to send the e-mail', ApiResponse::S500_INTERNAL_SERVER_ERROR, $e);
		} catch (InvalidAccountStateException $e) {
			throw new ClientErrorException('User is already verified', ApiResponse::S400_BAD_REQUEST, $e);
		}
		return $response->withStatus(ApiResponse::S200_OK);
	}

	#[Path('/verification/{uuid}')]
	#[Method('POST')]
	#[OpenApi('
		summary: Verifies the user
		responses:
			"200":
				description: Success
				content:
					application/json:
						schema:
							$ref: "#/components/schemas/UserSignedIn"
			"400":
				description: User is already verified
			"403":
				description: Account has been blocked
			"404":
				description: Not found
			"410":
				description: Verification link expired
	')]
	#[RequestParameter(name: 'uuid', type: 'integer', description: 'User verification UUID')]
	public function verify(ApiRequest $request, ApiResponse $response): ApiResponse {
		$baseUrl = BaseUrlHelper::get($request);
		try {
			$verification = $this->entityManager->getUserVerificationRepository()
				->getByUuid($request->getParameter('uuid'));
			$this->manager->verify($verification, $baseUrl);
		} catch (ResourceNotFoundException) {
			throw new ClientErrorException('User verification not found', ApiResponse::S404_NOT_FOUND);
		} catch (ResourceExpiredException) {
			throw new ClientErrorException('Verification link expired', ApiResponse::S410_GONE);
		} catch (InvalidAccountStateException) {
			throw new ClientErrorException('User is already verified', ApiResponse::S400_BAD_REQUEST);
		} catch (BlockedAccountException) {
			throw new ClientErrorException('User is blocked', ApiResponse::S403_FORBIDDEN);
		}
		$user = $verification->user;
		$json = [
			'info' => $user->jsonSerialize(),
			'token' => $this->manager->createJwt($user),
		];
		$response = $response->writeJsonBody($json)->withStatus(ApiResponse::S200_OK);
		return $this->validator->validateResponse('userSignedIn', $response);
	}

}
