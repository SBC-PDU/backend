<?php

/**
 * Copyright 2022-2024 Roman Ondráček <mail@romanondracek.cz>
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

declare(strict_types = 1);

namespace App\ApiModule\Controllers\Version1\Authentication;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\OpenApi;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\RequestParameter;
use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Exception\Api\ServerErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\ApiModule\Controllers\Version1\AuthenticationController;
use App\ApiModule\Utils\BaseUrlHelper;
use App\Exceptions\InvalidPasswordException;
use App\Exceptions\ResourceNotFoundException;
use BadMethodCallException;
use Nette\Mail\SendException;

/**
 * Password API controller
 */
#[Path('/password')]
class PasswordController extends AuthenticationController {

	#[Path('/recovery')]
	#[Method('POST')]
	#[OpenApi(<<<'EOT'
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
	EOT)]
	public function requestRecovery(ApiRequest $request, ApiResponse $response): ApiResponse {
		$baseUrl = BaseUrlHelper::get($request);
		$this->validator->validateRequest('passwordRecovery', $request);
		$body = $request->getJsonBodyCopy();
		try {
			$user = $this->userManager->getByEmail($body['email']);
			$this->userManager->createPasswordRecoveryRequest($user, $baseUrl);
		} catch (ResourceNotFoundException) {
			throw new ClientErrorException('User not found', ApiResponse::S404_NOT_FOUND);
		} catch (BadMethodCallException) {
			throw new ClientErrorException('E-mail address is not verified', ApiResponse::S403_FORBIDDEN);
		} catch (SendException $e) {
			throw new ServerErrorException('Unable to send the e-mail', ApiResponse::S500_INTERNAL_SERVER_ERROR, $e);
		}
		return $response->withStatus(ApiResponse::S200_OK);
	}

	#[Path('/reset/{uuid}')]
	#[Method('POST')]
	#[OpenApi(<<<'EOT'
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
			"403":
				description: User is blocked
			"404":
				description: Password recovery not found
			"410":
				description: Password recovery request is expired
	EOT)]
	#[RequestParameter(name: 'uuid', type: 'integer', description: 'Password recovery request UUID')]
	public function recover(ApiRequest $request, ApiResponse $response): ApiResponse {
		$this->validator->validateRequest('passwordReset', $request);
		$body = $request->getJsonBodyCopy();
		try {
			$recoveryRequest = $this->entityManager->getPasswordRecoveryRepository()
				->getByUuid($request->getParameter('uuid'));
			if ($recoveryRequest->isExpired()) {
				$this->entityManager->remove($recoveryRequest);
				$this->entityManager->flush();
				throw new ClientErrorException('Password recovery request is expired', ApiResponse::S410_GONE);
			}
			$user = $recoveryRequest->user;
			$user->setPassword($body['password']);
			$user->passwordRecovery = null;
			$this->entityManager->persist($user);
			$this->entityManager->flush();
			if ($user->state->isBlocked()) {
				throw new ClientErrorException('User is blocked', ApiResponse::S403_FORBIDDEN);
			}
			return $this->createSignedInResponse($response, $user);
		} catch (ResourceNotFoundException) {
			throw new ClientErrorException('Password recovery request not found', ApiResponse::S404_NOT_FOUND);
		} catch (InvalidPasswordException $e) {
			throw new ClientErrorException('Invalid password', ApiResponse::S400_BAD_REQUEST, $e);
		}
	}

	#[Path('/set/{uuid}')]
	#[Method('POST')]
	#[OpenApi(<<<'EOT'
		summary: Sets the password for invited user
		requestBody:
			required: true
			content:
				application/json:
					schema:
						$ref: "#/components/schemas/PasswordSet"
		responses:
			"200":
				description: Success
				content:
					application/json:
						schema:
							$ref: "#/components/schemas/UserSignedIn"
			"403":
				description: User is blocked
			"404":
				description: Password set not found
			"410":
				description: Password set request is expired
	EOT)]
	#[RequestParameter(name: 'uuid', type: 'integer', description: 'Password set UUID')]
	public function set(ApiRequest $request, ApiResponse $response): ApiResponse {
		$this->validator->validateRequest('passwordSet', $request);
		$body = $request->getJsonBodyCopy();
		try {
			$userInvitation = $this->entityManager->getUserInvitationRepository()
				->getByUuid($request->getParameter('uuid'));
			if ($userInvitation->isExpired()) {
				$this->entityManager->remove($userInvitation);
				$this->entityManager->flush();
				throw new ClientErrorException('Password set request is expired', ApiResponse::S410_GONE);
			}
			$user = $userInvitation->user;
			$user->setPassword($body['password']);
			$user->state = $user->state->verify();
			$user->invitation = null;
			$this->entityManager->persist($user);
			$this->entityManager->flush();
			if ($user->state->isBlocked()) {
				throw new ClientErrorException('User is blocked', ApiResponse::S403_FORBIDDEN);
			}
			return $this->createSignedInResponse($response, $user);
		} catch (ResourceNotFoundException) {
			throw new ClientErrorException('Password set request not found', ApiResponse::S404_NOT_FOUND);
		} catch (InvalidPasswordException) {
			throw new ClientErrorException('Invalid password', ApiResponse::S400_BAD_REQUEST);
		}
	}

}
