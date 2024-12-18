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

namespace App\ApiModule\Controllers\Version1;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\OpenApi;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\RequestParameter;
use Apitte\Core\Annotation\Controller\Tag;
use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\ApiModule\Models\RestApiSchemaValidator;
use App\Enums\MeasurementTimeRange;
use App\Models\Database\Entities\Device;
use App\Models\DeviceManager;
use ValueError;

/**
 * Device controller
 */
#[Path('/devices')]
#[Tag('Device')]
class DeviceController extends BaseController {

	/**
	 * Constructor
	 * @param DeviceManager $deviceManager Device manager
	 * @param RestApiSchemaValidator $validator REST API JSON schema validator
	 */
	public function __construct(
		private readonly DeviceManager $deviceManager,
		RestApiSchemaValidator $validator,
	) {
		parent::__construct($validator);
	}

	#[Path('/')]
	#[Method('GET')]
	#[OpenApi(<<<'EOT'
		summary: Lists all devices
		responses:
			"200":
				description: Success
				content:
					application/json:
						schema:
							$ref: "#/components/schemas/DeviceList"
	EOT)]
	public function list(ApiRequest $request, ApiResponse $response): ApiResponse {
		$response = $response->writeJsonBody($this->deviceManager->list());
		return $this->validator->validateResponse('deviceList', $response);
	}

	#[Path('/')]
	#[Method('POST')]
	#[OpenApi(<<<'EOT'
		summary: Adds new device
		requestBody:
			required: true
			content:
				application/json:
					schema:
						$ref: "#/components/schemas/DeviceAdd"
		responses:
			"201":
				description: Created
				headers:
					Location:
						description: Location of information about the created device
						schema:
							type: string
			"400":
				$ref: "#/components/responses/BadRequest"
			"409":
				description: Device ID is already used
	EOT)]
	public function add(ApiRequest $request, ApiResponse $response): ApiResponse {
		$this->validator->validateRequest('deviceAdd', $request);
		$json = $request->getJsonBodyCopy();
		$json['id'] = str_replace([':', '.', '-'], '', strtolower((string) $json['macAddress']));
		$device = Device::createFromJson($json);
		try {
			$this->deviceManager->add($device);
		} catch (ValueError) {
			throw new ClientErrorException('Device ID is already used', ApiResponse::S409_CONFLICT);
		}
		return $response->withStatus(ApiResponse::S201_CREATED)
			->withHeader('Location', '/v1/devices/' . $device->id);
	}

	#[Path('/{deviceId}')]
	#[Method('GET')]
	#[OpenApi(<<<'EOT'
		summary: Returns information about device
		responses:
			"200":
				description: Success
				content:
					application/json:
						schema:
							$ref: "#/components/schemas/DeviceDetail"
			"404":
				description: Device not found
	EOT)]
	#[RequestParameter(name: 'deviceId', type: 'string', description: 'Device ID')]
	public function get(ApiRequest $request, ApiResponse $response): ApiResponse {
		$device = $this->getDevice($request);
		$device->lastSeen = $this->deviceManager->getLastSeen($device)[$device->id] ?? null;
		$array = $device->jsonSerialize();
		foreach ($this->deviceManager->getLatestMeasurement($device) as $index => $measurements) {
			if (!array_key_exists($index, $array['outputs'])) {
				continue;
			}
			$array['outputs'][$index] = [...$array['outputs'][$index], ...$measurements];
		}
		$response = $response->writeJsonBody($array);
		return $this->validator->validateResponse('deviceDetail', $response);
	}

	#[Path('/{deviceId}')]
	#[Method('PUT')]
	#[OpenApi(<<<'EOT'
		summary: Modifies device
		requestBody:
			required: true
			content:
				application/json:
					schema:
						$ref: "#/components/schemas/DeviceModify"
		responses:
			"200":
				description: Success
			"400":
				$ref: "#/components/responses/BadRequest"
			"404":
				description: Device not found
	EOT)]
	#[RequestParameter(name: 'deviceId', type: 'string', description: 'Device ID')]
	public function edit(ApiRequest $request, ApiResponse $response): ApiResponse {
		$this->validator->validateRequest('deviceModify', $request);
		$json = $request->getJsonBodyCopy();
		$device = $this->getDevice($request);
		$device->editFromJson($json);
		$this->deviceManager->edit($device);
		return $response->withStatus(ApiResponse::S200_OK);
	}

	#[Path('/{deviceId}')]
	#[Method('DELETE')]
	#[OpenApi(<<<'EOT'
		summary: Deletes device
		responses:
			"200":
				description: Success
			"404":
				description: Device not found
	EOT)]
	public function delete(ApiRequest $request, ApiResponse $response): ApiResponse {
		$device = $this->getDevice($request);
		$this->deviceManager->delete($device);
		return $response->withStatus(ApiResponse::S200_OK);
	}

	#[Path('/{deviceId}/outputs/{outputId}')]
	#[Method('POST')]
	#[OpenApi(<<<'EOT'
		summary: Switches output
		responses:
			"200":
				description: Success
			"404":
				description: Device or output not found
	EOT)]
	#[RequestParameter(name: 'deviceId', type: 'string', description: 'Device ID')]
	#[RequestParameter(name: 'outputId', type: 'string', description: 'Output ID')]
	public function switch(ApiRequest $request, ApiResponse $response): ApiResponse {
		$device = $this->getDevice($request);
		$outputId = $request->getParameter('outputId');
		$state = $request->getJsonBodyCopy()['enabled'] === true;
		try {
			$this->deviceManager->switchOutput($device, (int) $outputId, $state);
		} catch (ValueError $error) {
			throw new ClientErrorException($error->getMessage(), ApiResponse::S404_NOT_FOUND);
		}
		return $response;
	}

	#[Path('/{deviceId}/measurements')]
	#[Method('GET')]
	#[OpenApi(<<<'EOT'
		summary: Returns measurements of device
		requestParameters:
			-
				name: timeRange
				in: query
				description: Time range
				required: false
				schema:
					type: string
					enum:
						- 5m
						- 15m
						- 6h
						- 12h
						- 1d
						- 1w
						- 1mo
		responses:
			"200":
				description: Success
				content:
					application/json:
						schema:
							$ref: "#/components/schemas/DeviceMeasurements"
	EOT)]
	#[RequestParameter(name: 'deviceId', type: 'string', description: 'Device ID')]
	public function measurement(ApiRequest $request, ApiResponse $response): ApiResponse {
		$device = $this->getDevice($request);
		$timeRange = MeasurementTimeRange::tryFrom($request->getQueryParam('timeRange')) ?? MeasurementTimeRange::Last1Hour;
		$response = $response->writeJsonBody($this->deviceManager->getMeasurements($device, $timeRange));
		return $this->validator->validateResponse('deviceMeasurements', $response);
	}

	/**
	 * Returns device from Device ID request parameter
	 * @param ApiRequest $request API request
	 * @return Device Device
	 */
	private function getDevice(ApiRequest $request): Device {
		$device = $this->deviceManager->get($request->getParameter('deviceId'));
		if (!$device instanceof Device) {
			throw new ClientErrorException('Device not found', ApiResponse::S404_NOT_FOUND);
		}
		return $device;
	}

}
