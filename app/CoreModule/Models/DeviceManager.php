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

namespace App\CoreModule\Models;

use App\CoreModule\Enums\MeasurementTimeRange;
use App\Models\Database\Entities\Device;
use App\Models\Database\EntityManager;
use App\Models\Database\Repositories\DeviceRepository;
use App\Models\MqttClientFactory;
use DateTime;
use InfluxDB2\Client as InfluxDBClient;
use InfluxDB2\Model\DeletePredicateRequest;
use InfluxDB2\Service\DeleteService;
use ValueError;

/**
 * Device manager
 */
class DeviceManager {

	/**
	 * @var DeviceRepository $repository Device database repository
	 */
	private readonly DeviceRepository $repository;

	/**
	 * Constructor
	 * @param EntityManager $entityManager Entity manager
	 * @param InfluxDBClient $influxDbClient InfluxDB client
	 * @param MqttClientFactory $mqttClientFactory MQTT client factory
	 */
	public function __construct(
		private readonly EntityManager $entityManager,
		private readonly InfluxDBClient $influxDbClient,
		private readonly MqttClientFactory $mqttClientFactory,
	) {
		$this->repository = $this->entityManager->getDeviceRepository();
	}

	/**
	 * Returns the list of devices
	 * @return array<int, Device> List of devices
	 */
	public function list(): array {
		$devices = $this->repository->findAll();
		$lastUpdated = $this->getLastSeen(null);
		return array_map(static function (Device $device) use ($lastUpdated): Device {
			$device->lastSeen = $lastUpdated[$device->id] ?? null;
			return $device;
		}, $devices);
	}

	/**
	 * Adds a new device
	 * @param Device $device Device to add
	 */
	public function add(Device $device): void {
		if ($this->get($device->id) !== null) {
			throw new ValueError('Device already exists');
		}
		$this->entityManager->persist($device);
		$this->entityManager->flush();
	}

	/**
	 * Returns the device by ID
	 * @param string $deviceId Device ID
	 * @return Device|null Device
	 */
	public function get(string $deviceId): ?Device {
		return $this->repository->findOneById($deviceId);
	}

	/**
	 * Edits the device
	 * @param Device $device Device to edit
	 */
	public function edit(Device $device): void {
		$this->entityManager->persist($device);
		$this->entityManager->flush();
	}

	/**
	 * Returns the last seen time
	 * @param Device|null $device Device
	 * @return array<string, DateTime> Last seen
	 */
	public function getLastSeen(?Device $device): array {
		$queryApi = $this->influxDbClient->createQueryApi();
		if ($device === null) {
			$deviceQuery = ' |> filter(fn: (r) => exists r["device"])';
		} else {
			$deviceQuery = ' |> filter(fn: (r) => r["device"] == "' . $device->id . '")';
		}
		$fluxQuery = 'from(bucket: "' . $this->influxDbClient->options['bucket'] . '")' .
			' |> range(start: 0, stop: now())' .
			$deviceQuery .
			' |> sort(columns: ["_time"], desc: false)' .
			' |> keep(columns: ["_time", "device"])' .
			' |> last(column: "_time")';
		$fluxTables = $queryApi->query($fluxQuery);
		$lastUpdated = [];
		foreach ($fluxTables as $fluxTable) {
			foreach ($fluxTable->records as $record) {
				$lastUpdated[(string) $record->values['device']] = new DateTime($record->getTime());
			}
		}
		return $lastUpdated;
	}

	/**
	 * Returns the latest measurements
	 * @param Device $device Device
	 * @return array<int, array<string, float>> Latest measurements
	 */
	public function getLatestMeasurement(Device $device): array {
		$array = [];
		$queryApi = $this->influxDbClient->createQueryApi();
		$fluxQuery = 'from(bucket: "' . $this->influxDbClient->options['bucket'] . '")' .
			' |> range(start: 0, stop: now())' .
			' |> filter(fn: (r) => exists r["output"])' .
			' |> filter(fn: (r) => r["device"] == "' . $device->id . '")' .
			' |> last()';
		$fluxTables = $queryApi->query($fluxQuery);
		foreach ($fluxTables as $fluxTable) {
			foreach ($fluxTable->records as $record) {
				$array[(int) $record->values['output'] - 1][$record->getMeasurement()] = $record->getValue();
			}
		}
		return $array;
	}

	/**
	 * Returns the measurements
	 * @param Device $device Device
	 * @return array<array{index: int, measurements: non-empty-array<string, array<array{time: string, value: float|null}>>}> Measurements
	 */
	public function getMeasurements(Device $device, MeasurementTimeRange $timeRange): array {
		$queryApi = $this->influxDbClient->createQueryApi();
		$fluxQuery = 'from(bucket: "' . $this->influxDbClient->options['bucket'] . '")' .
			' |> range(start: duration(v: -' . $timeRange->value . '))' .
			' |> filter(fn: (r) => r["device"] == "' . $device->id . '")' .
			' |> filter(fn: (r) => exists r["output"])' .
			' |> filter(fn: (r) => r["_measurement"] == "current" or r["_measurement"] == "voltage")' .
			' |> aggregateWindow(every: ' . $timeRange->getAggregationPeriod() . ', fn: mean, createEmpty: true)' .
			' |> yield(name: "mean")';
		$array = [];
		$fluxTables = $queryApi->query($fluxQuery);
		foreach ($fluxTables as $fluxTable) {
			foreach ($fluxTable->records as $record) {
				$outputIndex = (int) $record->values['output'];
				if (!array_key_exists($outputIndex, $array)) {
					$array[$record->values['output']] = [
						'index' => $outputIndex,
						'measurements' => [],
					];
				}
				$value = $record->getValue();
				$time = new DateTime($record->getTime());
				$array[$record->values['output']]['measurements'][$record->getMeasurement()][] = [
					'time' => $time->format('Y-m-d\TH:i:sp'),
					'value' => is_float($value) ? $value : null,
				];
			}
		}
		return array_values($array);
	}

	/**
	 * Deletes the device
	 * @param Device $device Device to delete
	 */
	public function delete(Device $device): void {
		// Delete device from database
		$this->entityManager->remove($device);
		$this->entityManager->flush();
		// Delete measurements from InfluxDB
		$service = $this->influxDbClient->createService(DeleteService::class);
		assert($service instanceof DeleteService);
		$predicate = new DeletePredicateRequest();
		$predicate->setStart(new DateTime('@0'));
		$predicate->setStop(new DateTime());
		$predicate->setPredicate('device = "' . $device->id . '"');
		$service->postDelete($predicate, null, $this->influxDbClient->options['org'], $this->influxDbClient->options['bucket']);
	}

	/**
	 * Switches the output
	 * @param Device $device Device
	 * @param int $index Output index
	 * @param bool $state Output state
	 */
	public function switchOutput(Device $device, int $index, bool $state): void {
		if (!$device->hasOutputIndex($index)) {
			throw new ValueError('PDU output #' . $index . ' not found');
		}
		$mqttClient = $this->mqttClientFactory->create();
		$mqttClient->connect($this->mqttClientFactory->getConnectionSettings(), true);
		$mqttClient->publish('sbc_pdu/' . $device->id . '/outputs/' . $index . '/enable', $state ? '1' : '0');
		$mqttClient->disconnect();
	}

}
