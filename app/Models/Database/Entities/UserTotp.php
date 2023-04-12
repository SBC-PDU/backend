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

namespace App\Models\Database\Entities;

use App\Models\Database\Attributes\TCreatedAt;
use App\Models\Database\Attributes\TUuid;
use App\Models\Database\Repositories\UserTotpRepository;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use OTPHP\TOTP;

/**
 * User TOTP 2FA
 */
#[ORM\Entity(repositoryClass: UserTotpRepository::class)]
#[ORM\Table(name: 'user_totp')]
#[ORM\HasLifecycleCallbacks]
class UserTotp implements JsonSerializable {

	use TUuid;
	use TCreatedAt;

	/**
	 * Constructor
	 * @param User $user User
	 * @param string $secret Secret
	 * @param string $name Name
	 */
	public function __construct(
		#[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'totp')]
		#[ORM\JoinColumn(name: 'user', onDelete: 'CASCADE')]
		public readonly User $user,
		#[ORM\Column(type: 'string', length: 32, unique: true)]
		public readonly string $secret,
		#[ORM\Column(type: 'string', length: 255, unique: true)]
		public string $name,
	) {
	}

	/**
	 * Verifies TOTP code
	 * @param string $code TOTP code
	 * @return bool Is the TOTP code valid?
	 */
	public function verify(string $code): bool {
		$totp = TOTP::createFromSecret($this->secret);
		return $totp->verify(otp: $code, leeway: 15);
	}

	/**
	 * Serializes TOTP entity into JSON
	 * @return array{uuid: string, name: string, createdAt: string} JSON serialized TOTP entity
	 */
	public function jsonSerialize(): array {
		return [
			'uuid' => $this->uuid->toString(),
			'name' => $this->name,
			'createdAt' => $this->createdAt->format('Y-m-d\TH:i:sp'),
		];
	}

}
