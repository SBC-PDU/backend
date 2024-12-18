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

namespace App\ApiModule\Version1\Models;

use App\Models\Database\Entities\User;
use App\Models\Database\EntityManager;
use Contributte\Middlewares\Security\IAuthenticator;
use InvalidArgumentException;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Nette\Utils\Strings;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class BearerAuthenticator implements IAuthenticator {

	/**
	 * Constructor
	 * @param JwtConfigurator $configurator JWT configurator
	 * @param EntityManager $entityManager Entity manager
	 */
	public function __construct(
		private readonly JwtConfigurator $configurator,
		private readonly EntityManager $entityManager,
	) {
	}

	/**
	 * @inheritDoc
	 * @throws InvalidArgumentException
	 */
	public function authenticate(ServerRequestInterface $request): ?User {
		$header = $request->getHeader('Authorization')[0] ?? '';
		$token = $this->parseAuthorizationHeader($header);
		if ($token === null || $token === '') {
			return null;
		}
		return $this->authenticateUser($token);
	}

	/**
	 * Authenticates the user
	 * @param non-empty-string $jwt User's JWT token
	 * @return User|null Authenticated user
	 */
	public function authenticateUser(string $jwt): ?User {
		$configuration = $this->configurator->create();
		$token = $configuration->parser()->parse($jwt);
		assert($token instanceof Plain);
		if (!$this->isJwtValid($token)) {
			return null;
		}
		try {
			$id = $token->claims()->get('uid');
			return $this->entityManager->getUserRepository()->find($id);
		} catch (Throwable) {
			return null;
		}
	}

	/**
	 * Parses the authorization header
	 * @param string $header Authorization header
	 * @return string|null JWT
	 */
	public function parseAuthorizationHeader(string $header): ?string {
		if (!str_starts_with($header, 'Bearer')) {
			return null;
		}
		$str = Strings::substring($header, 7);
		if ($str === '') {
			return null;
		}
		return $str;
	}

	/**
	 * Validates JWT
	 * @param Plain $token JWT to validate
	 * @return bool Is JWT valid?
	 */
	private function isJwtValid(Plain $token): bool {
		$configuration = $this->configurator->create();
		$validator = $configuration->validator();
		$signer = $configuration->signer();
		$verificationKey = $configuration->verificationKey();
		return $validator->validate(
			$token,
			new SignedWith($signer, $verificationKey),
			new LooseValidAt(SystemClock::fromSystemTimezone()),
		) && $token->claims()->has('uid');
	}

}
