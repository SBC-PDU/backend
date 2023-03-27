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

/**
 * Request content type utility
 */
class ContentTypeUtil {

	/**
	 * Returns Content-Type header if it exists
	 * @param ApiRequest $request API request
	 * @return string content-type header
	 * @throws ClientErrorException Missing Content-Type header
	 */
	public static function getContentType(ApiRequest $request): string {
		$contentType = $request->getHeader('Content-Type')[0] ?? null;
		if ($contentType === null) {
			throw new ClientErrorException('Missing content-type header', ApiResponse::S422_UNPROCESSABLE_ENTITY);
		}
		return $contentType;
	}

	/**
	 * Checks Content-Type string if it is valid
	 * @param ApiRequest $request API request
	 * @param array<string> $contentTypes array of allowed content types
	 * @throws ClientErrorException Invalid Content-Type
	 * @throws ClientErrorException Missing Content-Type header
	 */
	public static function validContentType(ApiRequest $request, array $contentTypes): string {
		$contentType = self::getContentType($request);
		foreach ($contentTypes as $item) {
			if (str_contains($contentType, $item)) {
				return $item;
			}
		}
		throw new ClientErrorException('Invalid content type', ApiResponse::S415_UNSUPPORTED_MEDIA_TYPE);
	}

}
