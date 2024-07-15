<?php

declare(strict_types = 1);

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

namespace App\Models\Database\Enums;

use App\Exceptions\InvalidAccountStateException;

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

	/// Invited account
	case Invited = 4;

	/// Blocked invited account
	case BlockedInvited = 5;

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
			self::Invited => 'invited',
			self::BlockedUnverified,
			self::BlockedVerified,
			self::BlockedInvited => 'blocked',
		};
	}

	/**
	 * Checks if the account is blocked
	 * @return bool In the account blocked?
	 */
	public function isBlocked(): bool {
		$blockedStates = [
			self::BlockedUnverified,
			self::BlockedVerified,
			self::BlockedInvited,
		];
		return in_array($this, $blockedStates, true);
	}

	/**
	 * Checks if the account is invited
	 * @return bool Is the account invited?
	 */
	public function isInvited(): bool {
		return $this === self::Invited || $this === self::BlockedInvited;
	}

	/**
	 * Checks if the account is verified
	 * @return bool Is the account verified?
	 */
	public function isVerified(): bool {
		return $this === self::Verified || $this === self::BlockedVerified;
	}

	/**
	 * Checks if the account is unverified
	 * @return bool Is the account unverified?
	 */
	public function isUnverified(): bool {
		return $this === self::Unverified || $this === self::BlockedUnverified;
	}

	/**
	 * Returns blocked account state based on the current state
	 * @return self Blocked account state
	 * @throws InvalidAccountStateException User is already blocked
	 */
	public function block(): self {
		return match ($this) {
			self::Unverified => self::BlockedUnverified,
			self::Verified => self::BlockedVerified,
			self::Invited => self::BlockedInvited,
			default => throw new InvalidAccountStateException(),
		};
	}

	/**
	 * Returns unblocked account state based on the current blocked state
	 * @return self Unblocked account state
	 * @throws InvalidAccountStateException User is already unblocked
	 */
	public function unblock(): self {
		return match ($this) {
			self::BlockedUnverified => self::Unverified,
			self::BlockedVerified => self::Verified,
			self::BlockedInvited => self::Invited,
			default => throw new InvalidAccountStateException('User is already unblocked'),
		};
	}

	/**
	 * Returns verified account state based on the current state
	 * @return self Verified account state
	 */
	public function verify(): self {
		return match ($this) {
			self::Unverified, self::Invited => self::Verified,
			self::BlockedUnverified, self::BlockedInvited => self::BlockedVerified,
			default => throw new InvalidAccountStateException('User is already verified'),
		};
	}

	/**
	 * Returns unverified account state based on the current state
	 * @return self Unverified account state
	 */
	public function unverify(): self {
		return match ($this) {
			self::Verified => self::Unverified,
			self::BlockedVerified => self::BlockedUnverified,
			default => throw new InvalidAccountStateException('User is already unverified'),
		};
	}

}
