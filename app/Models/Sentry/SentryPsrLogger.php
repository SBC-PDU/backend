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

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Sentry\Event;
use Sentry\SentrySdk;
use Sentry\Severity;
use Stringable;
use Throwable;

/**
 * Sentry PSR logger
 */
class SentryPsrLogger implements LoggerInterface {

	use LoggerTrait;

	/**
	 * Logs with an arbitrary level
	 * @param mixed $level Log level
	 * @param string|Stringable $message Log message
	 * @param array<mixed> $context Log context
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
	 */
	public function log($level, string|Stringable $message, array $context = []): void {
		if (array_key_exists('exception', $context) && $context['exception'] instanceof Throwable) {
			SentrySdk::getCurrentHub()->captureException($context['exception']);
		} else {
			$event = Event::createEvent();
			$event->setMessage((string) $message);
			if ($context !== []) {
				$event->setExtra($context);
			}
			$event->setLevel($this->mapLevel($level));
			SentrySdk::getCurrentHub()->captureEvent($event);
		}
	}

	/**
	 * Maps PSR log levels to Sentry log levels.
	 * @param string $level Level to map to Sentry level
	 * @return Severity Mapped level
	 */
	private function mapLevel(string $level): Severity {
		$map = [
			LogLevel::EMERGENCY => Severity::fatal(),
			LogLevel::ALERT => Severity::fatal(),
			LogLevel::CRITICAL => Severity::fatal(),
			LogLevel::ERROR => Severity::error(),
			LogLevel::WARNING => Severity::warning(),
			LogLevel::NOTICE => Severity::info(),
			LogLevel::INFO => Severity::info(),
			LogLevel::DEBUG => Severity::debug(),
		];
		return array_key_exists($level, $map) ? $map[$level] : Severity::info();
	}

}
