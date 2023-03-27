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
use Apitte\Core\Annotation\Controller\Tag;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\ApiModule\Version1\Models\OpenApiSchemaBuilder;
use App\ApiModule\Version1\Models\RestApiSchemaValidator;

/**
 * OpenAPI controller
 */
#[Path('/openapi')]
#[Tag('OpenAPI')]
class OpenApiController extends BaseController {

	/**
	 * Constructor
	 * @param OpenApiSchemaBuilder $schemaBuilder OpenAPI schema builder
	 * @param RestApiSchemaValidator $validator REST API JSON schema validator
	 */
	public function __construct(
		private readonly OpenApiSchemaBuilder $schemaBuilder,
		RestApiSchemaValidator $validator,
	) {
		parent::__construct($validator);
	}

	#[Path('/')]
	#[Method('GET')]
	#[OpenApi('
		summary: Returns OpenAPI schema
		security:
			- []
		responses:
			"200":
				description: Success
				content:
					application/json:
						schema:
							$ref: "#/components/schemas/OpenApiSpecification"
	')]
	public function index(ApiRequest $request, ApiResponse $response): ApiResponse {
		return $response->writeJsonBody($this->schemaBuilder->getArray());
	}

}
