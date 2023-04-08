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

namespace App\Models\Database\Repositories;

use App\Exceptions\ResourceNotFoundException;
use App\Models\Database\Entities\User;
use App\Models\Database\Entities\UserInvitation;
use Doctrine\ORM\EntityRepository;

/**
 * User verification repository
 * @extends EntityRepository<UserInvitation>
 */
class UserInvitationRepository extends EntityRepository {

	/**
	 * Finds the invitation by user
	 * @param User $user User
	 * @return array<UserInvitation> User invitation entities
	 */
	public function findByUser(User $user): array {
		return $this->findBy(['user' => $user->getId()]);
	}

	/**
	 * Finds the invitation by UUID
	 * @param string $uuid UUID
	 * @return UserInvitation|null User invitation entity
	 */
	public function findOneByUuid(string $uuid): ?UserInvitation {
		return $this->findOneBy(['uuid' => $uuid]);
	}

	/**
	 * Returns the invitation by UUID
	 * @param string $uuid Invitation UUID
	 * @return UserInvitation User invitation entity
	 * @throws ResourceNotFoundException Invitation not found
	 */
	public function getByUuid(string $uuid): UserInvitation {
		$invitation = $this->findOneBy(['uuid' => $uuid]);
		if (!$invitation instanceof UserInvitation) {
			throw new ResourceNotFoundException('Invitation not found');
		}
		return $invitation;
	}

}
