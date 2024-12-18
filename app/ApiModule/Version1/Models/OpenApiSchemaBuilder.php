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

namespace App\ApiModule\Version1\Models;

use Apitte\OpenApi\ISchemaBuilder;
use Nette\Utils\Strings;
use stdClass;

/**
 * OpenAPI JSON schema builder
 */
class OpenApiSchemaBuilder {

	/**
	 * Constructor
	 * @param ISchemaBuilder $schemaBuilder OpenAPI schema builder
	 */
	public function __construct(
		private readonly ISchemaBuilder $schemaBuilder,
	) {
	}

	/**
	 * Returns OpenAPI JSON schema
	 * @return array<string, mixed> OpenAPI JSON schema
	 */
	public function getArray(): array {
		$schema = $this->schemaBuilder->build()->toArray();
		$schema['paths']['/v1/openapi']['get']['security'] = [new stdClass()];
		$schema['paths']['/v1/openapi/schemas/{type}/{name}']['get']['security'] = [new stdClass()];
		$schema['paths']['/v1/auth/password/recovery']['post']['security'] = [new stdClass()];
		$schema['paths']['/v1/auth/password/reset/{uuid}']['post']['security'] = [new stdClass()];
		$schema['paths']['/v1/auth/sign/in']['post']['security'] = [new stdClass()];
		$schema['paths']['/v1/account/verification/{uuid}']['post']['security'] = [new stdClass()];
		foreach ($schema['servers'] as &$server) {
			$server['url'] .= 'v1/';
		}
		foreach ($schema['paths'] as $uri => $path) {
			$schema['paths'][Strings::replace($uri, '~/v1~', '')] = $path;
			unset($schema['paths'][$uri]);
		}
		foreach ($schema['paths'] as &$path) {
			foreach ($path as &$method) {
				if (!array_key_exists('security', $method)) {
					$method['responses']['401'] = ['$ref' => '#/components/responses/UnauthorizedError'];
				}
			}
		}
		return $schema;
	}

}
