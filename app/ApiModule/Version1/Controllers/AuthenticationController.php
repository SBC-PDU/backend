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
use App\ApiModule\Version1\Utils\BaseUrlHelper;
use App\CoreModule\Models\UserManager;
use App\Exceptions\InvalidPasswordException;
use App\Models\Database\Entities\PasswordRecovery;
use App\Models\Database\Entities\User;
use App\Models\Database\EntityManager;
use BadMethodCallException;
use Nette\Mail\SendException;

/**
 * Authentication API controller
 */
#[Path('/auth')]
#[Tag('Authentication')]
class AuthenticationController extends BaseController {

	/**
	 * Constructor
	 * @param EntityManager $entityManager Entity manager
	 * @param RestApiSchemaValidator $validator REST API JSON schema validator
	 */
	public function __construct(
		private readonly UserManager $userManager,
		private readonly EntityManager $entityManager,
		RestApiSchemaValidator $validator,
	) {
		parent::__construct($validator);
	}

	#[Path('/password/recovery')]
	#[Method('POST')]
	#[OpenApi('
		summary: Requests a password recovery
		requestBody:
			description: User e-mail address
			required: true
			content:
				application/json:
					schema:
						$ref: "#/components/schemas/PasswordRecovery"
		responses:
			"200":
				description: Success
			"403":
				description: E-mail address is not verified
			"404":
				description: User not found
			"500":
				description: Unable to send the e-mail
	')]
	public function requestPasswordRecovery(ApiRequest $request, ApiResponse $response): ApiResponse {
		$this->validator->validateRequest('passwordRecovery', $request);
		$body = $request->getJsonBody();
		$user = $this->userManager->findByEmail($body['email']);
		if ($user === null) {
			throw new ClientErrorException('User not found', ApiResponse::S404_NOT_FOUND);
		}
		try {
			$this->userManager->createPasswordRecoveryRequest($user, BaseUrlHelper::get($request));
		} catch (BadMethodCallException) {
			throw new ClientErrorException('E-mail address is not verified', ApiResponse::S403_FORBIDDEN);
		} catch (SendException $e) {
			throw new ServerErrorException('Unable to send the e-mail', ApiResponse::S500_INTERNAL_SERVER_ERROR, $e);
		}
		return $response->withStatus(ApiResponse::S200_OK);
	}

	#[Path('/password/reset/{uuid}')]
	#[Method('POST')]
	#[OpenApi('
		summary: Recovers the forgotten password
		requestBody:
			required: true
			content:
				application/json:
					schema:
						$ref: "#/components/schemas/PasswordReset"
		responses:
			"200":
				description: Success
				content:
					application/json:
						schema:
							$ref: "#/components/schemas/UserSignedIn"
			"404":
				description: Password recovery not found
			"410":
				description: Password recovery request is expired
	')]
	#[RequestParameter(name: 'uuid', type: 'integer', description: 'Password recovery request UUID')]
	public function recoverPassword(ApiRequest $request, ApiResponse $response): ApiResponse {
		$this->validator->validateRequest('passwordReset', $request);
		$body = $request->getJsonBody();
		$recoveryRequest = $this->entityManager->getPasswordRecoveryRepository()
			->findOneByUuid($request->getParameter('uuid'));
		if (!$recoveryRequest instanceof PasswordRecovery) {
			throw new ClientErrorException('Password recovery request not found', ApiResponse::S404_NOT_FOUND);
		}
		if ($recoveryRequest->isExpired()) {
			$this->entityManager->remove($recoveryRequest);
			$this->entityManager->flush();
			throw new ClientErrorException('Password recovery request is expired', ApiResponse::S410_GONE);
		}
		$user = $recoveryRequest->user;
		try {
			$user->setPassword($body['password']);
		} catch (InvalidPasswordException $e) {
			throw new ClientErrorException('Invalid password', ApiResponse::S400_BAD_REQUEST, $e);
		}
		$this->entityManager->persist($user);
		$this->entityManager->remove($recoveryRequest);
		$this->entityManager->flush();
		$json = [
			'info' => $user->jsonSerialize(),
			'token' => $this->userManager->createJwt($user),
		];
		$response = $response->writeJsonBody($json)
			->withStatus(ApiResponse::S200_OK);
		return $this->validator->validateResponse('userSignedIn', $response);
	}

	#[Path('/sign/in')]
	#[Method('POST')]
	#[OpenApi('
		summary: Signs in a user
		security:
			- []
		requestBody:
			description: User credentials
			required: true
			content:
				application/json:
					schema:
						$ref: "#/components/schemas/UserSignIn"
		responses:
			"200":
				description: Success
				content:
					application/json:
						schema:
							$ref: "#/components/schemas/UserSignedIn"
			"400":
				$ref: "#/components/responses/BadRequest"
			"403":
				description: Account has been blocked
			"500":
				$ref: "#/components/responses/ServerError"
	')]
	public function signIn(ApiRequest $request, ApiResponse $response): ApiResponse {
		$this->validator->validateRequest('userSignIn', $request);
		$credentials = $request->getJsonBody();
		$user = $this->userManager->findByEmail($credentials['email']);
		if (!$user instanceof User ||
			!$user->verifyPassword($credentials['password'])
		) {
			throw new ClientErrorException('Invalid credentials', ApiResponse::S400_BAD_REQUEST);
		}
		if ($user->state->isBlocked()) {
			throw new ClientErrorException('Account is blocked', ApiResponse::S403_FORBIDDEN);
		}
		$json = [
			'info' => $user->jsonSerialize(),
			'token' => $this->userManager->createJwt($user),
		];
		$response = $response->writeJsonBody($json)
			->withStatus(ApiResponse::S200_OK);
		return $this->validator->validateResponse('userSignedIn', $response);
	}

}
