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
use App\Models\Database\Entities\PasswordRecovery;
use App\Models\Database\Entities\User;
use Doctrine\ORM\EntityRepository;

/**
 * Password recovery repository
 * @extends EntityRepository<PasswordRecovery>
 */
class PasswordRecoveryRepository extends EntityRepository {

	/**
	 * Finds the password recovery by user
	 * @param User $user User
	 * @return array<PasswordRecovery> Password recovery entities
	 */
	public function findByUser(User $user): array {
		return $this->findBy(['user' => $user->getId()]);
	}

	/**
	 * Finds the password recovery by UUID
	 * @param string $uuid UUID
	 * @return PasswordRecovery|null User entity
	 */
	public function findOneByUuid(string $uuid): ?PasswordRecovery {
		return $this->findOneBy(['uuid' => $uuid]);
	}

	/**
	 * Returns the password recovery by UUID
	 * @param string $uuid Password recovery UUID
	 * @return PasswordRecovery Password recovery entity
	 * @throws ResourceNotFoundException Password recovery not found
	 */
	public function getByUuid(string $uuid): PasswordRecovery {
		$passwordRecovery = $this->findOneByUuid($uuid);
		if (!$passwordRecovery instanceof PasswordRecovery) {
			throw new ResourceNotFoundException('Password recovery not found');
		}
		return $passwordRecovery;
	}

}
