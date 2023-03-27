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

namespace App\ConsoleModule\Commands;

use App\ApiModule\Version1\Models\OpenApiSchemaBuilder;
use Nette\Utils\Json;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * OpenAPI specification command
 */
#[AsCommand(name: 'open-api:specification', description: 'Outputs OpenAPI specification')]
class OpenApiSpecificationCommand extends Command {

	/**
	 * Constructor
	 * @param string|null $name Command name
	 */
	public function __construct(
		private readonly OpenApiSchemaBuilder $schemaBuilder,
		?string $name = null,
	) {
		parent::__construct($name);
	}

	/**
	 * Executes the OpenAPI specification command
	 * @param InputInterface $input Command input
	 * @param OutputInterface $output Command output
	 * @return int Exit code
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$schema = $this->schemaBuilder->getArray();
		$output->writeln(Json::encode($schema));
		return 0;
	}

}
