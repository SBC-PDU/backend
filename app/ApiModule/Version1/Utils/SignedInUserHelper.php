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

namespace App\ApiModule\Version1\Utils;

use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\ApiModule\Version1\RequestAttributes;
use App\Models\Database\Entities\User;

/**
 * Signed-in user helper
 */
class SignedInUserHelper {

	/**
	 * Returns signed-in user
	 * @param ApiRequest $request API request
	 * @return User Signed-in user
	 * @throws ClientErrorException API key is used
	 */
	public static function get(ApiRequest $request): User {
		$user = $request->getAttribute(RequestAttributes::AppLoggedUser);
		if (!$user instanceof User) {
			throw new ClientErrorException('API key is used', ApiResponse::S403_FORBIDDEN);
		}
		return $user;
	}

}
