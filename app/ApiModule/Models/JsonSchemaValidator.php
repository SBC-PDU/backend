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

namespace App\ApiModule\Models;

use App\Exceptions\InvalidJsonException;
use App\Exceptions\NonexistentJsonSchemaException;
use JsonSchema\Validator;
use Nette\IOException;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use stdClass;

/**
 * API JSON schema validator
 */
class JsonSchemaValidator {

	/**
	 * @var string Path to directory with JSON schemas
	 */
	private string $schemaDir = __DIR__ . '/../../../../api/schemas';

	/**
	 * Validates JSON
	 * @param string $schema JSON schema file name
	 * @param array<mixed>|stdClass $json JSON to validate
	 * @throws InvalidJsonException
	 * @throws NonexistentJsonSchemaException
	 */
	public function validate(string $schema, array|stdClass $json): void {
		$this->checkExistence($schema);
		$validator = new Validator();
		try {
			$jsonSchema = Json::decode(FileSystem::read($this->schemaDir . '/' . $schema . '.json'), forceArrays: true);
		} catch (IOException $e) {
			throw new NonexistentJsonSchemaException('Cannot read JSON schema ' . $schema . '.', previous: $e);
		} catch (JsonException $e) {
			throw new NonexistentJsonSchemaException('Invalid JSON syntax in schema ' . $schema . '.', previous: $e);
		}
		$validator->validate($json, $jsonSchema);
		if (!$validator->isValid()) {
			$message = 'JSON does not validate. JSON schema: ' . $schema . ' Violations:';
			foreach ($validator->getErrors() as $error) {
				$message .= PHP_EOL . '[' . $error['property'] . '] ' . $error['message'];
			}
			throw new InvalidJsonException($message);
		}
	}

	/**
	 * Checks JSON schema existence
	 * @param string $schema JSON schema name
	 * @throws NonexistentJsonSchemaException
	 */
	private function checkExistence(string $schema): void {
		if (!file_exists($this->schemaDir . '/' . $schema . '.json')) {
			$message = 'Non-existing JSON schema ' . $schema . '.';
			throw new NonexistentJsonSchemaException($message);
		}
	}

}
