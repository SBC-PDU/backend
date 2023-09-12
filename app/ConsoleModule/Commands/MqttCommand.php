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

use App\CoreModule\Enums\MeasurementType;
use App\CoreModule\Models\DeviceManager;
use App\Models\Database\Entities\Device;
use App\Models\MqttClientFactory;
use InfluxDB2\Client as InfluxDBClient;
use InfluxDB2\Point;
use InfluxDB2\WriteType;
use PhpMqtt\Client\MqttClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * MQTT command
 */
#[AsCommand(name: 'mqtt', description: 'Runs MQTT bridge')]
class MqttCommand extends Command {

	/**
	 * Constructor
	 * @param MqttClientFactory $mqttClientFactory MQTT client factory
	 * @param string|null $name Command name
	 */
	public function __construct(
		private readonly MqttClientFactory $mqttClientFactory,
		private readonly InfluxDBClient $influxDbClient,
		private readonly DeviceManager $deviceManager,
		?string $name = null,
	) {
		parent::__construct($name);
	}

	/**
	 * Executes the MQTT command
	 * @param InputInterface $input Command input
	 * @param OutputInterface $output Command output
	 * @return int Exit code
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$mqttClient = $this->mqttClientFactory->create();
		$mqttClient->connect($this->mqttClientFactory->getConnectionSettings(), true);
		$writeApi = $this->influxDbClient->createWriteApi(['writeType' => WriteType::SYNCHRONOUS]);
		$measurementHandler = function (string $topic, string $message) use ($writeApi): void {
			$array = explode('/', $topic);
			$device = $this->deviceManager->get($array[1]);
			$output = (int) $array[3];
			$measurement = MeasurementType::tryFrom($array[4]);
			if (
				!$device instanceof Device ||
				$measurement === null ||
				$measurement === MeasurementType::NewState ||
				!$device->hasOutputIndex($output)
			) {
				return;
			}
			$writeApi->write($this->createMeasurementPoint($device, $output, $measurement, $message));
		};
		$statusHandler = function (string $topic, string $message) use ($writeApi): void {
			if ($message !== 'offline') {
				return;
			}
			$array = explode('/', $topic);
			$device = $this->deviceManager->get($array[1]);
			if (!$device instanceof Device) {
				return;
			}
			$measurements = [
				MeasurementType::Alert,
				MeasurementType::Current,
				MeasurementType::CurrentState,
				MeasurementType::Voltage,
			];
			foreach ($measurements as $measurement) {
				foreach ($device->outputs as $output) {
					$writeApi->write($this->createMeasurementPoint($device, $output->index, $measurement, '0'));
				}
			}
		};
		$mqttClient->subscribe('sbc_pdu/+/status', $statusHandler, MqttClient::QOS_EXACTLY_ONCE);
		$mqttClient->subscribe('sbc_pdu/+/outputs/+/+', $measurementHandler, MqttClient::QOS_EXACTLY_ONCE);
		$mqttClient->loop(true);
		$mqttClient->disconnect();
		$this->influxDbClient->close();
		return 0;
	}

	/**
	 * Creates measurement point
	 * @param Device $device Device
	 * @param int $output Output index
	 * @param MeasurementType $measurement Measurement type
	 * @param string $message Received message from MQTT
	 * @return Point Measurement point
	 */
	private function createMeasurementPoint(
		Device $device,
		int $output,
		MeasurementType $measurement,
		string $message,
	): Point {
		$boolMeasurements = [MeasurementType::Alert, MeasurementType::CurrentState];
		$value = in_array($measurement, $boolMeasurements, true) ? $message === '1' : (float) $message;
		return Point::measurement($measurement->value)
			->addTag('device', $device->id)
			->addTag('output', (string) $output)
			->addField('value', $value)
			->time(microtime(true));
	}

}
