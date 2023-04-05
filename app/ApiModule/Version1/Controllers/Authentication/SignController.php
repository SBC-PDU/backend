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

namespace App\ApiModule\Version1\Controllers\Authentication;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\OpenApi;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\ApiModule\Version1\Controllers\AuthenticationController;
use App\Models\Database\Entities\User;

/**
 * Sign in API controller
 */
#[Path('/sign')]
class SignController extends AuthenticationController {

	#[Path('/in')]
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
		return $this->createSignedInResponse($response, $user);
	}

}
