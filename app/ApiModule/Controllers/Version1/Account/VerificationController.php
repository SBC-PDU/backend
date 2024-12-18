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

namespace App\ApiModule\Controllers\Version1\Account;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\OpenApi;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\RequestParameter;
use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Exception\Api\ServerErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\ApiModule\Controllers\Version1\AccountController;
use App\ApiModule\Utils\BaseUrlHelper;
use App\ApiModule\Utils\SignedInUserHelper;
use App\Exceptions\BlockedAccountException;
use App\Exceptions\InvalidAccountStateException;
use App\Exceptions\ResourceExpiredException;
use App\Exceptions\ResourceNotFoundException;
use Nette\Mail\SendException;

/**
 * Account verification controller
 */
#[Path('/verification')]
class VerificationController extends AccountController {

	#[Path('/resend')]
	#[Method('POST')]
	#[OpenApi(<<<'EOT'
		summary: Resends the verification e-mail
		requestBody:
			required: false
			content:
				application/json:
					schema:
						$ref: "#/components/schemas/AccountResendVerification"
		responses:
			"200":
				description: Success
			"400":
				description: User is already verified
			"500":
				description: Unable to send the e-mail
	EOT)]
	public function resendVerification(ApiRequest $request, ApiResponse $response): ApiResponse {
		$baseUrl = BaseUrlHelper::get($request);
		$user = SignedInUserHelper::get($request);
		if ($request->getContentsCopy() !== '') {
			$this->validator->validateRequest('accountResendVerification', $request);
		}
		try {
			$this->manager->sendVerificationEmail($user, $baseUrl);
			return $response->withStatus(ApiResponse::S200_OK);
		} catch (SendException $e) {
			throw new ServerErrorException('Unable to send the e-mail', ApiResponse::S500_INTERNAL_SERVER_ERROR, $e);
		} catch (InvalidAccountStateException $e) {
			throw new ClientErrorException('User is already verified', ApiResponse::S400_BAD_REQUEST, $e);
		}
	}

	#[Path('/{uuid}')]
	#[Method('POST')]
	#[OpenApi(<<<'EOT'
		summary: Verifies the user
		requestBody:
			required: false
			content:
				application/json:
					schema:
						$ref: "#/components/schemas/AccountVerify"
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
	EOT)]
	#[RequestParameter(name: 'uuid', type: 'integer', description: 'User verification UUID')]
	public function verify(ApiRequest $request, ApiResponse $response): ApiResponse {
		$baseUrl = BaseUrlHelper::get($request);
		if ($request->getContentsCopy() !== '') {
			$this->validator->validateRequest('accountVerify', $request);
		}
		try {
			$verification = $this->entityManager->getUserVerificationRepository()
				->getByUuid($request->getParameter('uuid'));
			$this->manager->verify($verification, $baseUrl);
			return $this->createSignedInResponse($response, $verification->user);
		} catch (ResourceNotFoundException) {
			throw new ClientErrorException('User verification not found', ApiResponse::S404_NOT_FOUND);
		} catch (ResourceExpiredException) {
			throw new ClientErrorException('Verification link expired', ApiResponse::S410_GONE);
		} catch (InvalidAccountStateException) {
			throw new ClientErrorException('User is already verified', ApiResponse::S400_BAD_REQUEST);
		} catch (BlockedAccountException) {
			throw new ClientErrorException('User is blocked', ApiResponse::S403_FORBIDDEN);
		}
	}

}
