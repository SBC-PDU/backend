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

namespace App\Models;

use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

/**
 * MQTT client factory
 */
class MqttClientFactory {

	/**
	 * Constructor
	 * @param string $host MQTT broker host
	 * @param int $port MQTT broker port
	 * @param string $clientId MQTT client ID
	 * @param bool $useTls Use TLS
	 * @param string|null $username MQTT username
	 * @param string|null $password MQTT password
	 */
	public function __construct(
		private readonly string $host,
		private readonly int $port,
		private readonly string $clientId,
		private readonly bool $useTls = false,
		private readonly ?string $username = null,
		private readonly ?string $password = null,
	) {
	}

	/**
	 * Creates a new MQTT client instance
	 * @return MqttClient MQTT client instance
	 */
	public function create(): MqttClient {
		return new MqttClient(
			host: $this->host,
			port: $this->port,
			clientId: $this->clientId . posix_getpid(),
			protocol: MqttClient::MQTT_3_1,
		);
	}

	/**
	 * Returns the connection settings
	 * @return ConnectionSettings Connection settings
	 */
	public function getConnectionSettings(): ConnectionSettings {
		$connectionSettings = (new ConnectionSettings())
			->setUsername($this->username)
			->setPassword($this->password);
		if ($this->useTls) {
			$connectionSettings = $connectionSettings
				->setUseTls($this->useTls)
				->setTlsSelfSignedAllowed(false)
				->setTlsVerifyPeer(true);
		}
		return $connectionSettings;
	}

}
