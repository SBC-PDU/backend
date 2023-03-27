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

use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\OpenApi;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\RequestParameter;
use Apitte\Core\Annotation\Controller\Tag;
use Apitte\Core\Exception\Api\ClientErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\ApiModule\Version1\Models\RestApiSchemaValidator;
use App\ApiModule\Version1\Utils\BaseUrlHelper;
use App\CoreModule\Models\UserManager;
use App\Exceptions\InvalidEmailAddressException;
use App\Models\Database\Entities\User;
use App\Models\Database\EntityManager;
use App\Models\Database\Enums\UserLanguage;
use App\Models\Database\Enums\UserRole;
use App\Models\Database\Repositories\UserRepository;
use BadMethodCallException;
use Nette\Mail\SendException;
use ValueError;

/**
 * User manager API controller
 */
#[Path('/users')]
#[Tag('User manager')]
class UsersController extends BaseController {

	/**
	 * @var UserRepository User database repository
	 */
	private readonly UserRepository $repository;

	/**
	 * Constructor
	 * @param EntityManager $entityManager Entity manager
	 * @param UserManager $manager User manager
	 * @param RestApiSchemaValidator $validator REST API JSON schema validator
	 */
	public function __construct(
		private readonly EntityManager $entityManager,
		private readonly UserManager $manager,
		RestApiSchemaValidator $validator,
	) {
		$this->repository = $entityManager->getUserRepository();
		parent::__construct($validator);
	}

	#[Path('/')]
	#[Method('GET')]
	#[OpenApi('
		summary: Lists all users
		responses:
			"200":
				description: Success
				content:
					application/json:
						schema:
							type: array
							items:
								$ref: "#/components/schemas/UserDetail"
			"403":
				$ref: "#/components/responses/Forbidden"
	')]
	public function list(ApiRequest $request, ApiResponse $response): ApiResponse {
		self::checkScopes($request, ['admin']);
		return $response->writeJsonBody($this->manager->list([]));
	}

	#[Path('/')]
	#[Method('POST')]
	#[OpenApi('
		summary: Creates a new user
		requestBody:
			required: true
			content:
				application/json:
					schema:
						$ref: "#/components/schemas/UserAdd"
		responses:
			"201":
				description: Created
				headers:
					Location:
						description: Location of information about the created user
						schema:
							type: string
			"400":
				$ref: "#/components/responses/BadRequest"
			"403":
				$ref: "#/components/responses/Forbidden"
			"409":
				description: E-mail address is already used
	')]
	public function create(ApiRequest $request, ApiResponse $response): ApiResponse {
		$baseUrl = BaseUrlHelper::get($request);
		self::checkScopes($request, ['admin']);
		$this->validator->validateRequest('userAdd', $request);
		$json = $request->getJsonBody();
		try {
			if ($this->manager->checkEmailUniqueness($json['email'])) {
				throw new ClientErrorException('E-main address is already used', ApiResponse::S409_CONFLICT);
			}
			$user = User::createFromJson($json);
			$this->manager->create($user);
		} catch (InvalidEmailAddressException $e) {
			throw new ClientErrorException('Invalid email address: ' . $e->getMessage(), ApiResponse::S400_BAD_REQUEST, $e);
		}
		$responseBody = ['emailSent' => false];
		try {
			$this->manager->sendVerificationEmail($user, $baseUrl);
			$responseBody['emailSent'] = true;
		} catch (SendException $e) {
			// Ignore failure
		}
		return $response->withStatus(ApiResponse::S201_CREATED)
			->withHeader('Location', '/v1/users/' . $user->getId())
			->writeJsonBody($responseBody);
	}

	#[Path('/{id}')]
	#[Method('GET')]
	#[OpenApi('
		summary: Finds user by ID
		responses:
			"200":
				description: Success
				content:
					application/json:
						schema:
							$ref: "#/components/schemas/UserDetail"
			"403":
				$ref: "#/components/responses/Forbidden"
			"404":
				description: Not found
	')]
	#[RequestParameter(name: 'id', type: 'integer', description: 'User ID')]
	public function get(ApiRequest $request, ApiResponse $response): ApiResponse {
		self::checkScopes($request, ['admin']);
		$response = $response->writeJsonObject($this->getUser($request));
		return $this->validator->validateResponse('userDetail', $response);
	}

	#[Path('/{id}')]
	#[Method(('DELETE'))]
	#[OpenApi('
		summary: Deletes a user
		responses:
			"200":
				description: Success
			"403":
				$ref: "#/components/responses/Forbidden"
			"404":
				description: Not found
	')]
	#[RequestParameter(name: 'id', type: 'integer', description: 'User ID')]
	public function delete(ApiRequest $request, ApiResponse $response): ApiResponse {
		self::checkScopes($request, ['admin']);
		try {
			$this->manager->delete($this->getUser($request));
		} catch (BadMethodCallException $e) {
			throw new ClientErrorException('Admin user deletion forbidden for the only admin user', ApiResponse::S409_CONFLICT);
		}
		return $response->withStatus(ApiResponse::S200_OK);
	}

	#[Path('/{id}')]
	#[Method('PUT')]
	#[OpenApi('
		summary: Edits user
		requestBody:
			required: true
			content:
				application/json:
					schema:
						$ref: "#/components/schemas/UserEdit"
		responses:
			"200":
				description: Success
			"400":
				$ref: "#/components/responses/BadRequest"
			"403":
				$ref: "#/components/responses/Forbidden"
			"404":
				description: Not found
			"409":
				description: Username is already used
	')]
	#[RequestParameter(name: 'id', type: 'integer', description: 'User ID')]
	public function edit(ApiRequest $request, ApiResponse $response): ApiResponse {
		self::checkScopes($request, ['admin']);
		$baseUrl = BaseUrlHelper::get($request);
		$user = $this->getUser($request);
		$this->validator->validateRequest('userEdit', $request);
		$json = $request->getJsonBody();
		$user->name = $json['name'];
		$email = $json['email'];
		if ($this->manager->checkEmailUniqueness($email, $user->getId())) {
			throw new ClientErrorException('E-mail address is already used', ApiResponse::S409_CONFLICT);
		}
		try {
			$user->setEmail($email);
		} catch (InvalidEmailAddressException $e) {
			throw new ClientErrorException($e->getMessage(), ApiResponse::S400_BAD_REQUEST, $e);
		}
		if (($user->role === UserRole::Admin) &&
			($this->repository->userCountByRole(UserRole::Admin) === 1) &&
			($json['role'] !== UserRole::Admin->value)) {
				throw new ClientErrorException('Admin user role change forbidden for the only admin user', ApiResponse::S409_CONFLICT);
		}
		try {
			$user->role = UserRole::from($json['role']);
		} catch (ValueError $e) {
			throw new ClientErrorException('Invalid role', ApiResponse::S400_BAD_REQUEST, $e);
		}
		try {
			$user->language = UserLanguage::from($json['language']);
		} catch (ValueError $e) {
			throw new ClientErrorException('Invalid language', ApiResponse::S400_BAD_REQUEST, $e);
		}
		$this->entityManager->persist($user);
		if ($user->hasChangedEmail()) {
			try {
				$this->manager->sendVerificationEmail($user, $baseUrl);
			} catch (SendException $e) {
				// Ignore failure
			}
		}
		$this->entityManager->flush();
		return $response->withStatus(ApiResponse::S200_OK);
	}

	#[Path('/{id}/block')]
	#[Method('POST')]
	#[OpenApi('
		summary: Blocks a user
		responses:
			"200":
				description: Success
			"403":
				$ref: "#/components/responses/Forbidden"
			"404":
				description: Not found
			"409":
				description: User is already blocked
	')]
	public function block(ApiRequest $request, ApiResponse $response): ApiResponse {
		self::checkScopes($request, ['admin']);
		try {
			$this->manager->block($this->getUser($request));
			return $response->withStatus(ApiResponse::S200_OK);
		} catch (BadMethodCallException $e) {
			throw new ClientErrorException('User is already blocked', ApiResponse::S409_CONFLICT, $e);
		}
	}

	#[Path('/{id}/unblock')]
	#[Method('POST')]
	#[OpenApi('
		summary: Unblocks a user
		responses:
			"200":
				description: Success
			"403":
				$ref: "#/components/responses/Forbidden"
			"404":
				description: Not found
			"409":
				description: User is not blocked
	')]
	public function unblock(ApiRequest $request, ApiResponse $response): ApiResponse {
		self::checkScopes($request, ['admin']);
		try {
			$this->manager->unblock($this->getUser($request));
			return $response->withStatus(ApiResponse::S200_OK);
		} catch (BadMethodCallException $e) {
			throw new ClientErrorException('User is not blocked', ApiResponse::S409_CONFLICT, $e);
		}
	}

	/**
	 * Returns the user from User ID request parameter
	 * @param ApiRequest $request API request
	 * @return User User
	 * @throws ClientErrorException User not found
	 */
	private function getUser(ApiRequest $request): User {
		$id = (int) $request->getParameter('id');
		$user = $this->repository->find($id);
		if (!$user instanceof User) {
			throw new ClientErrorException('User not found', ApiResponse::S404_NOT_FOUND);
		}
		return $user;
	}

}
