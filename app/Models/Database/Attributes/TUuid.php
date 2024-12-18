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

namespace App\Models\Database\Attributes;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

/**
 * UUID attribute
 */
trait TUuid {

	/**
	 * @var UuidInterface|null UUID
	 */
	#[ORM\Id]
	#[ORM\GeneratedValue(strategy: 'CUSTOM')]
	#[ORM\CustomIdGenerator(class: UuidGenerator::class)]
	#[ORM\Column(
		type: 'uuid',
		unique: true,
	)]
	protected ?UuidInterface $uuid = null;

	/**
	 * Returns UUID
	 * @return UuidInterface|null UUID
	 */
	public function getUuid(): ?UuidInterface {
		return $this->uuid;
	}

	/**
	 * Creates a deep copy
	 */
	public function __clone() {
		$this->uuid = null;
	}

}
