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

namespace App\ApiModule\Version1\Controllers;

use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\Tag;
use Apitte\Core\Http\ApiResponse;
use App\ApiModule\Version1\Models\RestApiSchemaValidator;
use App\CoreModule\Models\UserManager;
use App\Models\Database\Entities\User;
use App\Models\Database\EntityManager;

/**
 * Authentication API controller
 */
#[Path('/auth')]
#[Tag('Authentication')]
abstract class AuthenticationController extends BaseController {

	/**
	 * Constructor
	 * @param EntityManager $entityManager Entity manager
	 * @param RestApiSchemaValidator $validator REST API JSON schema validator
	 */
	public function __construct(
		protected readonly UserManager $userManager,
		protected readonly EntityManager $entityManager,
		RestApiSchemaValidator $validator,
	) {
		parent::__construct($validator);
	}

	/**
	 * Creates signed in user response
	 * @param ApiResponse $response API response
	 * @param User $user User to sign in
	 * @return ApiResponse Signed in user response
	 */
	protected function createSignedInResponse(ApiResponse $response, User $user): ApiResponse {
		$json = [
			'info' => $user->jsonSerialize(),
			'token' => $this->userManager->createJwt($user),
		];
		$response = $response->writeJsonBody($json)->withStatus(ApiResponse::S200_OK);
		return $this->validator->validateResponse('userSignedIn', $response);
	}

}
