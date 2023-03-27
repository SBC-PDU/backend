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

namespace App\Models\Database\Entities;

use App\Models\Database\Attributes\TCreatedAt;
use App\Models\Database\Repositories\DeviceRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Nette\Utils\Strings;
use Tracy\Debugger;
use Tracy\Logger;

/**
 * Device entity
 */
#[ORM\Entity(repositoryClass: DeviceRepository::class)]
#[ORM\Table(name: 'devices')]
#[ORM\HasLifecycleCallbacks]
class Device implements JsonSerializable {

	use TCreatedAt;

	/**
	 * @var DateTime|null Last seen
	 */
	public ?DateTime $lastSeen = null;

	/**
	 * @var Collection<int, DeviceOutput> Device outputs
	 */
	#[ORM\OneToMany(mappedBy: 'device', targetEntity: DeviceOutput::class, cascade: ['all'], orphanRemoval: true)]
	public Collection $outputs;

	/**
	 * Constructor
	 * @param string $id Device ID
	 * @param string $name Device name
	 */
	public function __construct(
		#[ORM\Id]
		#[ORM\Column(type: 'string', length: 12, unique: true, nullable: false)]
		public readonly string $id,
		#[ORM\Column(type: 'string', length: 255, nullable: false)]
		public string $name,
	) {
		$this->outputs = new ArrayCollection();
	}

	/**
	 * Creates device entity from JSON serialized device entity
	 * @param array{id: string, name: string, macAddress: string, outputs: array<array{index: int, name: string}>} $json JSON serialized device entity
	 * @return self Device entity
	 */
	public static function createFromJson(array $json): self {
		$device = new self($json['id'], $json['name']);
		foreach ($json['outputs'] as $output) {
			$device->addOutput(DeviceOutput::createFromJson($output, $device));
		}
		return $device;
	}

	/**
	 * Edits device entity from JSON serialized device entity
	 * @param array{name: string, outputs: array<array{index: int, name: string}>} $json JSON serialized device entity
	 */
	public function editFromJson(array $json): void {
		$this->name = $json['name'];
		$currentOutputIndexes = $this->outputs->map(static fn (DeviceOutput $output): int => $output->index)->toArray();
		$newOutputIndexes = array_map(static fn (array $output): int => $output['index'], $json['outputs']);
		foreach (array_diff($currentOutputIndexes, $newOutputIndexes) as $indexToRemove) {
			$output = $this->outputs->findFirst(static fn (int $key, DeviceOutput $value): bool => $value->index === $indexToRemove);
			if ($output instanceof DeviceOutput) {
				$this->outputs->removeElement($output);
			}
		}
		foreach ($json['outputs'] as $newOutput) {
			$output = $this->outputs->findFirst(static fn (int $key, DeviceOutput $value): bool => $value->index === $newOutput['index']);
			if ($output instanceof DeviceOutput) {
				$output->name = $newOutput['name'];
			} else {
				$this->addOutput(DeviceOutput::createFromJson($newOutput, $this));
			}
		}
	}

	/**
	 * Adds a new output to the device
	 * @param DeviceOutput $output Output to add to the device
	 */
	public function addOutput(DeviceOutput $output): void {
		$this->outputs->add($output);
	}

	/**
	 * Checks if output exists
	 * @param int $outputIndex Output index
	 * @return bool True if output exists
	 */
	public function hasOutputIndex(int $outputIndex): bool {
		return $this->outputs->exists(static fn (int $key, DeviceOutput $value): bool => $value->index === $outputIndex);
	}

	/**
	 * Returns Device entity as JSON serializable array
	 * @return array{id: string, name: string, macAddress: string, outputs: array<int, mixed>, createdAt: string, lastSeen: string|null} JSON serializable array
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->id,
			'name' => $this->name,
			'macAddress' => Strings::replace($this->id, '~(..)(?!$)\.?~', '\1:'),
			'outputs' => $this->outputs->map(static fn (DeviceOutput $output): array => $output->jsonSerialize())->toArray(),
			'createdAt' => $this->createdAt->format('Y-m-d\TH:i:sp'),
			'lastSeen' => $this->lastSeen?->format('Y-m-d\TH:i:sp'),
		];
	}

}
