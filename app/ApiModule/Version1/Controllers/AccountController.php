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
use App\Exceptions\InvalidEmailAddressException;
use App\Exceptions\InvalidPasswordException;
use App\Models\Database\Entities\User;
use App\Models\Database\EntityManager;
use App\Models\Database\Enums\AccountState;
use App\Models\Database\Enums\UserLanguage;
use Nette\Mail\SendException;
use ValueError;

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
			"409":
				description: Username or e-mail address is already used
	')]
	public function edit(ApiRequest $request, ApiResponse $response): ApiResponse {
		$baseUrl = BaseUrlHelper::get($request);
		$user = $request->getAttribute(RequestAttributes::AppLoggedUser);
		assert($user instanceof User);
		$this->validator->validateRequest('accountEdit', $request);
		$json = $request->getJsonBody();
		$user->name = $json['name'];
		try {
			$user->language = UserLanguage::from($json['language']);
		} catch (ValueError $e) {
			throw new ClientErrorException('Invalid language', ApiResponse::S400_BAD_REQUEST, $e);
		}
			$email = $json['email'];
		if ($this->manager->checkEmailUniqueness($email, $user->getId())) {
			throw new ClientErrorException('E-main address is already used', ApiResponse::S409_CONFLICT);
		}
		try {
			$user->setEmail($email);
		} catch (InvalidEmailAddressException $e) {
			throw new ClientErrorException($e->getMessage(), ApiResponse::S400_BAD_REQUEST, $e);
		}
		if (($json['changePassword'] ?? false) === true) {
			try {
				$user->changePassword($json['oldPassword'], $json['newPassword']);
			} catch (InvalidPasswordException $e) {
				throw new ClientErrorException($e->getMessage(), ApiResponse::S400_BAD_REQUEST, $e);
			}
		}
		$this->entityManager->persist($user);
		if ($user->hasChangedEmail()) {
			try {
				$this->manager->sendVerificationEmail($user, $baseUrl);
			} catch (SendException $e) {
				// Ignore failure
			}
		}
		$this->entityManager->flush();
		return $response->withStatus(ApiResponse::S200_OK);
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
		if ($user->state === AccountState::Verified) {
			throw new ClientErrorException('User is already verified', ApiResponse::S400_BAD_REQUEST);
		}
		try {
			$this->manager->sendVerificationEmail($user, $baseUrl);
		} catch (SendException $e) {
			throw new ServerErrorException('Unable to send the e-mail', ApiResponse::S500_INTERNAL_SERVER_ERROR, $e);
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
		$repository = $this->entityManager->getUserVerificationRepository();
		$verification = $repository->findOneByUuid($request->getParameter('uuid'));
		if ($verification === null) {
			throw new ClientErrorException('User verification not found', ApiResponse::S404_NOT_FOUND);
		}
		$user = $verification->user;
		switch ($user->state) {
			case AccountState::Verified:
				throw new ClientErrorException('User is already verified', ApiResponse::S400_BAD_REQUEST);
			case AccountState::Unverified:
				if ($verification->isExpired()) {
					$this->manager->sendVerificationEmail($user, $baseUrl);
					throw new ClientErrorException('Verification link expired', ApiResponse::S410_GONE);
				}
				$user->state = AccountState::Verified;
				$this->entityManager->persist($user);
				break;
			case AccountState::BlockedUnverified:
			case AccountState::BlockedVerified:
				throw new ClientErrorException('User has been blocked', ApiResponse::S403_FORBIDDEN);
		}
		$this->entityManager->flush();
		$json = [
			'info' => $user->jsonSerialize(),
			'token' => $this->manager->createJwt($user),
		];
		$response = $response->writeJsonBody($json)
			->withStatus(ApiResponse::S200_OK);
		return $this->validator->validateResponse('userSignedIn', $response);
	}

}
