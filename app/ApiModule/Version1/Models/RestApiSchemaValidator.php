<?php

declare(strict_types = 1);

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

namespace App\ApiModule\Version1\Models;

use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Exception\Api\ServerErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\CoreModule\Exceptions\InvalidJsonException;
use App\CoreModule\Exceptions\NonexistentJsonSchemaException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Tracy\Debugger;

/**
 * REST API JSON schema validator
 */
class RestApiSchemaValidator {

	/**
	 * @param JsonSchemaValidator $validator JSON schema validator
	 */
	public function __construct(
		private readonly JsonSchemaValidator $validator,
	) {
	}

	/**
	 * Validates REST API request
	 * @param string $schema REST API JSON schema name
	 * @param ApiRequest $request REST API request
	 * @throws ClientErrorException
	 */
	public function validateRequest(string $schema, ApiRequest $request): void {
		try {
			$this->validator->validate('requests/' . $schema, $request->getJsonBodyCopy(false));
		} catch (JsonException $e) {
			throw new ClientErrorException('Invalid JSON syntax', ApiResponse::S400_BAD_REQUEST, $e);
		} catch (InvalidJsonException $e) {
			throw new ClientErrorException($e->getMessage(), ApiResponse::S400_BAD_REQUEST, $e);
		} catch (NonexistentJsonSchemaException $e) {
			throw new ServerErrorException($e->getMessage(), ApiResponse::S500_INTERNAL_SERVER_ERROR, $e);
		}
	}

	/**
	 * Validates REST API response
	 * @param string $schema REST API JSON schema name
	 * @param ApiResponse $response REST API response
	 * @return ApiResponse REST API response
	 */
	public function validateResponse(string $schema, ApiResponse $response): ApiResponse {
		$body = $response->getContents(rewind: true);
		$response->rewindBody();
		if ($body === '') {
			return $response;
		}
		try {
			$jsonBody = Json::decode($body);
			$this->validator->validate('responses/' . $schema, $jsonBody);
		} catch (JsonException | InvalidJsonException | NonexistentJsonSchemaException $e) {
			Debugger::log($e, Debugger::WARNING);
		}
		return $response;
	}

}
