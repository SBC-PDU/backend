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

namespace App\Models\Database\Attributes;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Created at attribute
 */
trait TCreatedAt {

	/**
	 * @var DateTime Created at time
	 */
	#[ORM\Column(type: 'datetime', nullable: false)]
	private DateTime $createdAt;

	/**
	 * Generate created at time
	 * @internal
	 */
	#[ORM\PrePersist]
	public function generateCreatedAt(): void {
		$this->createdAt = new DateTime();
	}

	/**
	 * Returns created at time
	 * @return DateTime Created at time
	 */
	public function getCreatedAt(): DateTime {
		return $this->createdAt;
	}

	/**
	 * Sets created at time
	 * @param DateTime|null $dateTime Creation date
	 * @internal
	 */
	public function setCreatedAt(?DateTime $dateTime = null): void {
		$this->createdAt = $dateTime ?? new DateTime();
	}

}
