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

namespace App\ConsoleModule\Commands;

use App\Models\Database\EntityManager;
use Symfony\Component\Console\Command\Command;

/**
 * CLI command with entity manager
 */
abstract class EntityManagerCommand extends Command {

	/**
	 * Constructor
	 * @param EntityManager $entityManager Entity manager
	 * @param string|null $name Command name
	 */
	public function __construct(
		protected readonly EntityManager $entityManager,
		?string $name = null,
	) {
		parent::__construct($name);
	}

}
