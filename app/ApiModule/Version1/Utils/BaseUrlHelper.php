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

use Apitte\Core\Http\ApiRequest;
use Nette\Utils\JsonException;

/**
 * Base URL helper
 */
class BaseUrlHelper {

	/**
	 * Returns base URL from request
	 * @param ApiRequest $request API request
	 * @return string Base URL
	 */
	public static function get(ApiRequest $request): string {
		try {
			$jsonBody = $request->getJsonBodyCopy();
			if (array_key_exists('baseUrl', $jsonBody)) {
				return trim($jsonBody['baseUrl'], '/');
			}
		} catch (JsonException) {
			// Ignore
		}
		$path = $request->getUri()->getPath();
		if ($path === '') {
			return (string) $request->getUri();
		}
		return explode($path, (string) $request->getUri(), 2)[0];
	}

}
