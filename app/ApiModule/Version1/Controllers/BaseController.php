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

namespace App\ApiModule\Version1\Controllers;

use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use Apitte\Core\UI\Controller\IController;
use App\ApiModule\Version1\Models\RestApiSchemaValidator;
use App\ApiModule\Version1\RequestAttributes;
use App\Models\Database\Entities\User;

/**
 * Base API controller
 */
#[Path('/v1')]
abstract class BaseController implements IController {

	/**
	 * Constructor
	 * @param RestApiSchemaValidator $validator REST API JSON schema validator
	 */
	public function __construct(
		protected readonly RestApiSchemaValidator $validator,
	) {
	}

	/**
	 * Checks the scopes
	 * @param ApiRequest $request API request
	 * @param array<string> $scopes Supported scopes
	 */
	protected function checkScopes(ApiRequest $request, array $scopes): void {
		$user = $request->getAttribute(RequestAttributes::AppLoggedUser);
		if ($user instanceof User && array_intersect($scopes, $user->getScopes()) === []) {
			throw new ClientErrorException('Insufficient permissions.', ApiResponse::S403_FORBIDDEN);
		}
	}

}
