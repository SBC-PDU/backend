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

namespace App\ApiModule\Version1\Controllers\Authentication;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\OpenApi;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\ApiModule\Version1\Controllers\AuthenticationController;
use App\ApiModule\Version1\Utils\SignedInUserHelper;

/**
 * JWT token controller
 */
#[Path('/token')]
class TokenController extends AuthenticationController {

	#[Path('/refresh')]
	#[Method('POST')]
	#[OpenApi('
		summary: Refreshes a JWT token
		responses:
			"200":
				description: Success
				content:
					application/json:
						schema:
							$ref: "#/components/schemas/UserSignedIn"
			"403":
				$ref: "#/components/responses/Forbidden"
	')]
	public function signIn(ApiRequest $request, ApiResponse $response): ApiResponse {
		$user = SignedInUserHelper::get($request);
		return $this->createSignedInResponse($response, $user);
	}

}
