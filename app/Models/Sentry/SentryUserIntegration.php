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

namespace App\Models\Sentry;

use App\ApiModule\Version1\Models\BearerAuthenticator;
use App\Models\Database\Entities\User;
use Contributte\Sentry\Integration\BaseIntegration;
use Nette\DI\Container;
use Nette\Http\IRequest;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\State\HubInterface;
use Sentry\UserDataBag;

class SentryUserIntegration extends BaseIntegration {

	/**
	 * Constructor
	 * @param Container $container Nette DI container
	 * @param BearerAuthenticator $authenticator Bearer authenticator
	 */
	public function __construct(
		protected readonly Container $container,
		protected readonly BearerAuthenticator $authenticator,
	) {
	}

	public function setup(HubInterface $hub, Event $event, ?EventHint $hint): ?Event {
		$httpRequest = $this->container->getByType(IRequest::class, false);

		// There is no http request
		if (!$httpRequest instanceof IRequest) {
			return $event;
		}

		$header = $httpRequest->getHeader('Authorization') ?? '';
		if ($header === '') {
			return $event;
		}
		$token = $this->authenticator->parseAuthorizationHeader($header);

		if ($token === null || $token === '') {
			return $event;
		}

		$user = $this->authenticator->authenticateUser($token);

		if ($user instanceof User) {
			$userDataBag = new UserDataBag();
			$userDataBag->setUsername($user->name);
			$userDataBag->setEmail($user->getEmail());
			$userDataBag->setIpAddress($httpRequest->getRemoteAddress());
			$userDataBag->setMetadata('role', $user->role);
			$userDataBag->setMetadata('state', $user->state->name);

			$event->setUser($userDataBag);
		}

		return $event;
	}

}
