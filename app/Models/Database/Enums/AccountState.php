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

namespace App\Models\Database\Enums;

/**
 * Account state enum
 */
enum AccountState: int {

	/// Unverified account
	case Unverified = 0;

	/// Verified account
	case Verified = 1;

	/// Blocked unverified account

	case BlockedUnverified = 2;

	/// Blocked verified account
	case BlockedVerified = 3;

	/// Default account state
	public const Default = self::Unverified;

	/**
	 * Returns account state as string
	 * @return string Account state as string
	 */
	public function toString(): string {
		return match ($this) {
			self::Unverified => 'unverified',
			self::Verified => 'verified',
			self::BlockedUnverified, self::BlockedVerified => 'blocked',
		};
	}

	/**
	 * Checks if the account is blocked
	 * @return bool In the account blocked?
	 */
	public function isBlocked(): bool {
		return in_array($this, [self::BlockedUnverified, self::BlockedVerified], true);
	}

}
