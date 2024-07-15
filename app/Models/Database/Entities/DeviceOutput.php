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

namespace App\Models\Database\Entities;

use App\Models\Database\Repositories\DeviceOutputRepository;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * Device output entity
 */
#[ORM\Entity(repositoryClass: DeviceOutputRepository::class)]
#[ORM\Table(name: 'device_outputs')]
#[ORM\HasLifecycleCallbacks]
class DeviceOutput implements JsonSerializable {

	/**
	 * Constructor
	 * @param int $index Output index
	 * @param string $name Output name
	 * @param Device $device Device entity
	 */
	public function __construct(
		#[ORM\Id]
		#[ORM\Column(name: '`index`', type: 'integer', nullable: false)]
		public readonly int $index,
		#[ORM\Column(type: 'string', length: 255, nullable: false)]
		public string $name,
		#[ORM\Id]
		#[ORM\ManyToOne(targetEntity: Device::class, inversedBy: 'outputs')]
		#[ORM\JoinColumn(name: 'device', referencedColumnName: 'id', onDelete: 'CASCADE')]
		public readonly Device $device,
	) {
	}

	/**
	 * Creates device output entity from JSON serialized device output entity
	 * @param array{index: int, name: string} $json JSON serialized device output entity
	 * @param Device $device Device
	 * @return self Device output entity
	 */
	public static function createFromJson(array $json, Device $device): self {
		return new self($json['index'], $json['name'], $device);
	}

	/**
	 * Returns device output entity as JSON serializable array
	 * @return array{index: int, name: string} JSON serializable array
	 */
	public function jsonSerialize(): array {
		return [
			'index' => $this->index,
			'name' => $this->name,
		];
	}

}
