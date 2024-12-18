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

namespace App\Models\Database\Repositories;

use App\Exceptions\ResourceNotFoundException;
use App\Models\Database\Entities\User;
use App\Models\Database\Enums\UserRole;
use Doctrine\ORM\EntityRepository;

/**
 * User repository
 * @extends EntityRepository<User>
 */
class UserRepository extends EntityRepository {

	/**
	 * Finds the user by e-mail address
	 * @param string $email E-mail address
	 * @return User|null User entity
	 */
	public function findOneByEmail(string $email): ?User {
		return $this->findOneBy(['email' => $email]);
	}

	/**
	 * Returns the user by e-mail address
	 * @param string $email E-mail address
	 * @return User User entity
	 * @throws ResourceNotFoundException User not found
	 */
	public function getByEmail(string $email): User {
		$user = $this->findOneByEmail($email);
		if (!$user instanceof User) {
			throw new ResourceNotFoundException('User not found');
		}
		return $user;
	}

	/**
	 * Returns the user by ID
	 * @param int $id User ID
	 * @return User User entity
	 * @throws ResourceNotFoundException User not found
	 */
	public function getById(int $id): User {
		$user = $this->find($id);
		if (!$user instanceof User) {
			throw new ResourceNotFoundException('User not found');
		}
		return $user;
	}

	/**
	 * Returns count of users of a specific role
	 * @param UserRole $role User role
	 * @return int Number of users of a specific role
	 */
	public function userCountByRole(UserRole $role): int {
		return $this->count(['role' => $role->value]);
	}

}
