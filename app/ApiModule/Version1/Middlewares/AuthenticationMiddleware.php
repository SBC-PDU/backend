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

namespace App\ApiModule\Version1\Middlewares;

use Apitte\Core\Http\ApiResponse;
use App\ApiModule\Version1\RequestAttributes;
use App\Models\Database\Entities\User;
use Contributte\Middlewares\IMiddleware;
use Contributte\Middlewares\Security\IAuthenticator;
use InvalidArgumentException;
use Nette\Utils\Json;
use Nette\Utils\Strings;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Authentication middleware
 */
class AuthenticationMiddleware implements IMiddleware {

	/**
	 * Whitelisted paths
	 */
	private const WHITELISTED_PATHS = [
		'/v1/auth/password/recovery' => ['POST'],
		'/v1/auth/sign/in' => ['POST'],
		'/v1/openapi' => ['GET'],
	];

	/**
	 * Constructor
	 * @param IAuthenticator $authenticator Authenticator
	 */
	public function __construct(
		private readonly IAuthenticator $authenticator,
	) {
	}

	/**
	 * Checks if the path is whitelisted
	 * @param ServerRequestInterface $request API request
	 * @return bool Is the path whitelisted?
	 */
	protected function isWhitelisted(ServerRequestInterface $request): bool {
		$requestUrl = rtrim($request->getUri()->getPath(), '/');
		$uuidRegex = '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-4[0-9a-fA-F]{3}-[89abAB][0-9a-fA-F]{3}-[0-9a-fA-F]{12}';
		if (Strings::match($requestUrl, '~^/v1/account/verification/' . $uuidRegex . '$~') !== null ||
			Strings::match($requestUrl, '~^/v1/auth/password/set/' . $uuidRegex . '$~') !== null ||
			Strings::match($requestUrl, '~^/v1/auth/password/reset/' . $uuidRegex . '$~') !== null) {
			return true;
		}
		if (in_array($requestUrl, array_keys(self::WHITELISTED_PATHS), true)) {
			return in_array($request->getMethod(), self::WHITELISTED_PATHS[$requestUrl], true);
		}
		return false;
	}

	/**
	 * Creates unauthorized response
	 * @param ResponseInterface $response Response to modify
	 * @param string $message Message
	 * @return ResponseInterface Response
	 */
	private function createUnauthorizedResponse(ResponseInterface $response, string $message): ResponseInterface {
		$json = Json::encode(['error' => $message]);
		$response->getBody()->write($json);
		return $response->withStatus(ApiResponse::S401_UNAUTHORIZED)
			->withHeader('WWW-Authenticate', 'Bearer')
			->withHeader('Content-Type', 'application/json');
	}

	public function __invoke(
		ServerRequestInterface $request,
		ResponseInterface $response,
		callable $next,
	): ResponseInterface {
		if ($this->isWhitelisted($request)) {
			// Pass to next middleware
			return $next($request, $response);
		}
		try {
			$identity = $this->authenticator->authenticate($request);
		} catch (InvalidArgumentException $e) {
			return $this->createUnauthorizedResponse($response, 'Invalid JWT');
		}
		// If we have an identity, then go to next middleware, otherwise stop and return current response
		if ($identity === null) {
			return $this->createUnauthorizedResponse($response, 'Client authentication failed');
		}
		if ($identity instanceof User) {
			if ($identity->state->isBlocked()) {
				return $this->createUnauthorizedResponse($response, 'Account is blocked');
			}
			// Add info about current logged user to request attributes
			$request = $request->withAttribute(RequestAttributes::AppLoggedUser, $identity);
		}
		// Pass to next middleware
		return $next($request, $response);
	}

}
