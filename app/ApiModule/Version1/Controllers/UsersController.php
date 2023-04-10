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
use Apitte\Core\Exception\Api\ServerErrorException;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\ApiModule\Version1\Models\RestApiSchemaValidator;
use App\ApiModule\Version1\RequestAttributes;
use App\ApiModule\Version1\Utils\BaseUrlHelper;
use App\CoreModule\Models\UserManager;
use App\Exceptions\ConflictedEmailAddressException;
use App\Exceptions\InvalidAccountStateException;
use App\Exceptions\InvalidEmailAddressException;
use App\Exceptions\InvalidUserLanguageException;
use App\Exceptions\InvalidUserRoleException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Database\Entities\User;
use App\Models\Database\Enums\UserRole;
use BadMethodCallException;
use Nette\Mail\SendException;

/**
 * User manager API controller
 */
#[Path('/users')]
#[Tag('User manager')]
class UsersController extends BaseController {

	/**
	 * Constructor
	 * @param UserManager $manager User manager
	 * @param RestApiSchemaValidator $validator REST API JSON schema validator
	 */
	public function __construct(
		private readonly UserManager $manager,
		RestApiSchemaValidator $validator,
	) {
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
		self::checkScopes($request, ['admin']);
		$this->validator->validateRequest('userAdd', $request);
		$baseUrl = BaseUrlHelper::get($request);
		$json = $request->getJsonBodyCopy();
		try {
			$user = User::createFromJson($json);
			$this->manager->create($user, $baseUrl);
		} catch (InvalidEmailAddressException $e) {
			throw new ClientErrorException('Invalid email address: ' . $e->getMessage(), ApiResponse::S400_BAD_REQUEST, $e);
		} catch (ConflictedEmailAddressException) {
			throw new ClientErrorException('E-main address is already used', ApiResponse::S409_CONFLICT);
		}
		return $response->withStatus(ApiResponse::S201_CREATED)
			->withHeader('Location', '/v1/users/' . $user->getId());
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
			"409":
				description: Admin user deletion forbidden for the single admin user
	')]
	#[RequestParameter(name: 'id', type: 'integer', description: 'User ID')]
	public function delete(ApiRequest $request, ApiResponse $response): ApiResponse {
		self::checkScopes($request, ['admin']);
		try {
			$this->manager->delete($this->getUser($request));
		} catch (BadMethodCallException $e) {
			throw new ClientErrorException('Admin user deletion forbidden for the single admin user', ApiResponse::S409_CONFLICT);
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
		$this->validator->validateRequest('userEdit', $request);
		$baseUrl = BaseUrlHelper::get($request);
		$user = $this->getUser($request);
		$json = $request->getJsonBodyCopy();
		try {
			if (($user->role === UserRole::Admin) &&
				$this->manager->hasOnlySingleAdmin() &&
				($json['role'] !== UserRole::Admin->value)) {
				throw new ClientErrorException('Admin user role change forbidden for the only admin user', ApiResponse::S409_CONFLICT);
			}
			$user->editFromJson($json);
			$this->manager->edit($user, $baseUrl);
			return $response->withStatus(ApiResponse::S200_OK);
		} catch (InvalidEmailAddressException $e) {
			throw new ClientErrorException($e->getMessage(), ApiResponse::S400_BAD_REQUEST);
		} catch (InvalidUserLanguageException) {
			throw new ClientErrorException('Invalid language', ApiResponse::S400_BAD_REQUEST);
		} catch (InvalidUserRoleException) {
			throw new ClientErrorException('Invalid role', ApiResponse::S400_BAD_REQUEST);
		} catch (ConflictedEmailAddressException) {
			throw new ClientErrorException('E-mail address is already used', ApiResponse::S409_CONFLICT);
		}
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
			$user = $this->getUser($request);
			$currentUser = $request->getAttribute(RequestAttributes::AppLoggedUser);
			if ($currentUser instanceof User && $currentUser->getId() === $user->getId()) {
				throw new ClientErrorException('User cannot block itself', ApiResponse::S400_BAD_REQUEST);
			}
			$this->manager->block($user);
			return $response->withStatus(ApiResponse::S200_OK);
		} catch (InvalidAccountStateException $e) {
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
		} catch (InvalidAccountStateException $e) {
			throw new ClientErrorException('User is not blocked', ApiResponse::S409_CONFLICT, $e);
		}
	}

	#[Path('/{id}/resend')]
	#[Method('POST')]
	#[OpenApi('
		summary: Resends invitation or verification e-mail
		responses:
			"200":
				description: Success
			"400":
				description: User is not in invited or unverified state
				content:
					"application/json":
						schema:
							$ref: "#/components/schemas/Error"
			"403":
				$ref: "#/components/responses/Forbidden"
			"404":
				description: Not found
	')]
	#[RequestParameter(name: 'id', type: 'integer', description: 'User ID')]
	public function resend(ApiRequest $request, ApiResponse $response): ApiResponse {
		self::checkScopes($request, ['admin']);
		$user = $this->getUser($request);
		$baseUrl = BaseUrlHelper::get($request);
		try {
			if ($user->state->isInvited()) {
				$this->manager->sendInvitationEmail($user, $baseUrl);
			} elseif ($user->state->isUnverified()) {
				$this->manager->sendVerificationEmail($user, $baseUrl);
			} else {
				throw new ClientErrorException('User is not in invited or unverified state', ApiResponse::S400_BAD_REQUEST);
			}
			return $response->withStatus(ApiResponse::S200_OK);
		} catch (SendException $e) {
			throw new ServerErrorException('Unable to send the e-mail', ApiResponse::S500_INTERNAL_SERVER_ERROR, $e);
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
		try {
			return $this->manager->getById($id);
		} catch (ResourceNotFoundException $e) {
			throw new ClientErrorException('User not found', ApiResponse::S404_NOT_FOUND, $e);
		}
	}

}
