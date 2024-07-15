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

namespace App\Models\Database\Repositories;

use App\Exceptions\ResourceNotFoundException;
use App\Models\Database\Entities\User;
use App\Models\Database\Entities\UserTotp;
use Doctrine\ORM\EntityRepository;

/**
 * User TOTP 2FA repository
 * @extends EntityRepository<UserTotp>
 */
class UserTotpRepository extends EntityRepository {

	/**
	 * Finds the TOTP by user
	 * @param User $user User
	 * @return array<UserTotp> User TOTP entities
	 */
	public function findByUser(User $user): array {
		return $this->findBy(['user' => $user->getId()]);
	}

	/**
	 * Finds the TOTP by UUID
	 * @param string $uuid UUID
	 * @return UserTotp|null User TOTP entity
	 */
	public function findOneByUuid(string $uuid): ?UserTotp {
		return $this->findOneBy(['uuid' => $uuid]);
	}

	/**
	 * Returns the TOTP by UUID
	 * @param string $uuid Verification UUID
	 * @return UserTotp User TOTP entity
	 * @throws ResourceNotFoundException Verification not found
	 */
	public function getByUuid(string $uuid): UserTotp {
		$totp = $this->findOneByUuid($uuid);
		if (!$totp instanceof UserTotp) {
			throw new ResourceNotFoundException('TOTP not found');
		}
		return $totp;
	}

}
