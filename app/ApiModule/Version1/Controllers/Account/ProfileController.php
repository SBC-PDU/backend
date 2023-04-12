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

namespace App\ApiModule\Version1\Controllers\Account;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\OpenApi;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\ApiModule\Version1\Controllers\AccountController;
use App\ApiModule\Version1\Utils\BaseUrlHelper;
use App\ApiModule\Version1\Utils\SignedInUserHelper;
use App\Exceptions\ConflictedEmailAddressException;
use App\Exceptions\IncorrectPasswordException;
use App\Exceptions\InvalidEmailAddressException;
use App\Exceptions\InvalidPasswordException;
use App\Exceptions\InvalidUserLanguageException;

/**
 * Account controller
 */
#[Path('/')]
class ProfileController extends AccountController {

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
		return $response->writeJsonObject(SignedInUserHelper::get($request));
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
				content:
					application/json:
						schema:
							$ref: "#/components/schemas/UserSignedIn"
			"400":
				$ref: "#/components/responses/BadRequest"
			"403":
				$ref: "#/components/responses/Forbidden"
			"409":
				description: Username or e-mail address is already used
	')]
	public function edit(ApiRequest $request, ApiResponse $response): ApiResponse {
		$baseUrl = BaseUrlHelper::get($request);
		$user = SignedInUserHelper::get($request);
		$this->validator->validateRequest('accountEdit', $request);
		$json = $request->getJsonBodyCopy();
		try {
			$user->editFromJson($json);
			if (($json['changePassword'] ?? false) === true) {
				$user->changePassword($json['oldPassword'], $json['newPassword']);
			}
			$this->manager->edit($user, $baseUrl);
			return $this->createSignedInResponse($response, $user);
		} catch (InvalidEmailAddressException | InvalidPasswordException | IncorrectPasswordException $e) {
			throw new ClientErrorException($e->getMessage(), ApiResponse::S400_BAD_REQUEST);
		} catch (InvalidUserLanguageException) {
			throw new ClientErrorException('Invalid language', ApiResponse::S400_BAD_REQUEST);
		} catch (ConflictedEmailAddressException) {
			throw new ClientErrorException('E-mail address is already used', ApiResponse::S409_CONFLICT);
		}
	}

}
