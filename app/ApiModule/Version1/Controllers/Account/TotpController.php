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

namespace App\ApiModule\Version1\Controllers\Account;

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\OpenApi;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\RequestParameter;
use Apitte\Core\Annotation\Controller\Tag;
use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\ApiModule\Version1\Controllers\BaseController;
use App\ApiModule\Version1\Models\RestApiSchemaValidator;
use App\ApiModule\Version1\Utils\SignedInUserHelper;
use App\CoreModule\Models\TotpManager;
use App\Exceptions\IncorrectPasswordException;
use App\Exceptions\IncorrectTotpCodeException;
use App\Exceptions\ResourceNotFoundException;

/**
 * TOTP 2FA controller
 */
#[Path('/account/totp')]
#[Tag('TOTP 2FA management')]
class TotpController extends BaseController {

	/**
	 * Constructor
	 * @param TotpManager $manager TOTP manager
	 * @param RestApiSchemaValidator $validator REST API JSON schema validator
	 */
	public function __construct(
		protected readonly TotpManager $manager,
		RestApiSchemaValidator $validator,
	) {
		parent::__construct($validator);
	}

	#[Path('/')]
	#[Method('GET')]
	#[OpenApi('
		summary: Lists all TOTP 2FA
		responses:
			"200":
				description: TOTP 2FA list
				content:
					application/json:
						schema:
							type: array
							items:
								$ref: "#/components/schemas/TotpDetail"
			"403":
				$ref: "#/components/responses/Forbidden"
	')]
	public function list(ApiRequest $request, ApiResponse $response): ApiResponse {
		$user = SignedInUserHelper::get($request);
		return $response->writeJsonBody($this->manager->list($user));
	}

	#[Path('/')]
	#[Method('POST')]
	#[OpenApi('
		summary: Registers new TOTP 2FA
		requestBody:
			required: true
			content:
				application/json:
					schema:
						$ref: "#/components/schemas/TotpAdd"
		responses:
			"201":
				description: Created
				headers:
					Location:
						description: Location of information about the created TOTP
						schema:
							type: string
			"400":
				description: Incorrect TOTP code or password
			"403":
				$ref: "#/components/responses/Forbidden"
	')]
	public function add(ApiRequest $request, ApiResponse $response): ApiResponse {
		//$baseUrl = BaseUrlHelper::get($request);
		$user = SignedInUserHelper::get($request);
		$this->validator->validateRequest('userTotpAdd', $request);
		$json = $request->getJsonBodyCopy();
		try {
			$totp = $this->manager->add($user, $json);
			return $response->withStatus(ApiResponse::S201_CREATED)
				->withHeader('Location', '/v1/account/totp/' . $totp->getUuid());
		} catch (IncorrectTotpCodeException) {
			throw new ClientErrorException('Incorrect TOTP code', ApiResponse::S400_BAD_REQUEST);
		} catch (IncorrectPasswordException) {
			throw new ClientErrorException('Incorrect password', ApiResponse::S400_BAD_REQUEST);
		}
	}

	#[Path('/{uuid}')]
	#[Method('GET')]
	#[OpenApi('
		summary: Gets information about TOTP 2FA
		responses:
			"200":
				description: TOTP 2FA information
				content:
					application/json:
						schema:
							$ref: "#/components/schemas/TotpDetail"
			"403":
				$ref: "#/components/responses/Forbidden"
			"404":
				description: TOTP token not found
	')]
	#[RequestParameter(name: 'uuid', type: 'string', description: 'TOTP UUID')]
	public function get(ApiRequest $request, ApiResponse $response): ApiResponse {
		try {
			$user = SignedInUserHelper::get($request);
			$totp = $this->manager->get($user, $request->getParameter('uuid'));
			return $response->writeJsonObject($totp);
		} catch (ResourceNotFoundException) {
			throw new ClientErrorException('TOTP token not found', ApiResponse::S404_NOT_FOUND);
		}
	}

	#[Path('/{uuid}')]
	#[Method('DELETE')]
	#[OpenApi('
		summary: Deletes TOTP 2FA
		requestBody:
			required: true
			content:
				application/json:
					schema:
						$ref: "#/components/schemas/TotpDelete"
		responses:
			"200":
				description: Success
			"400":
				description: Incorrect TOTP code or password
			"403":
				$ref: "#/components/responses/Forbidden"
			"404":
				description: TOTP token not found
	')]
	#[RequestParameter(name: 'uuid', type: 'string', description: 'TOTP UUID')]
	public function delete(ApiRequest $request, ApiResponse $response): ApiResponse {
		$user = SignedInUserHelper::get($request);
		$this->validator->validateRequest('userTotpDelete', $request);
		$json = $request->getJsonBodyCopy();
		try {
			$this->manager->delete($user, $request->getParameter('uuid'), $json['code'], $json['password']);
		} catch (ResourceNotFoundException) {
			throw new ClientErrorException('TOTP token not found', ApiResponse::S404_NOT_FOUND);
		} catch (IncorrectPasswordException) {
			throw new ClientErrorException('Incorrect password', ApiResponse::S400_BAD_REQUEST);
		} catch (IncorrectTotpCodeException) {
			throw new ClientErrorException('Incorrect TOTP code', ApiResponse::S400_BAD_REQUEST);
		}
		return $response->withStatus(ApiResponse::S200_OK);
	}

}
