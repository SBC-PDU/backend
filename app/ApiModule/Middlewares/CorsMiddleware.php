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

namespace App\ApiModule\Middlewares;

use Contributte\Middlewares\IMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Cross-Origin Resource Sharing middleware
 */
class CorsMiddleware implements IMiddleware {

	/**
	 * Middleware invocation
	 * @param ServerRequestInterface $request Request
	 * @param ResponseInterface $response Response
	 * @param callable(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface $next
	 * @return ResponseInterface Response
	 */
	public function __invoke(
		ServerRequestInterface $request,
		ResponseInterface $response,
		callable $next,
	): ResponseInterface {
		// Add CORS headers
		if ($request->getMethod() === 'OPTIONS') {
			return $response->withHeader('Access-Control-Allow-Origin', '*')
				->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, OPTIONS, DELETE')
				->withHeader('Access-Control-Allow-Headers', 'Accept, Content-Type, Authorization, sentry-trace, baggage')
				->withHeader('Access-Control-Expose-Headers', '*');
		}
		$response = $response->withHeader('Access-Control-Allow-Origin', '*')
			->withHeader('Access-Control-Allow-Headers', 'Accept, Content-Type, Authorization, sentry-trace, baggage')
			->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, DELETE, PUT')
			->withHeader('Access-Control-Expose-Headers', '*');
		// Pass to next middleware
		return $next($request, $response);
	}

}
