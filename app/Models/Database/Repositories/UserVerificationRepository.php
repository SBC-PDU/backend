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

use App\Models\Database\Entities\UserVerification;
use Doctrine\ORM\EntityRepository;

/**
 * User verification repository
 * @extends EntityRepository<UserVerification>
 */
class UserVerificationRepository extends EntityRepository {

	/**
	 * Finds the verification by UUID
	 * @param string $uuid UUID
	 * @return UserVerification|null User verification entity
	 */
	public function findOneByUuid(string $uuid): ?UserVerification {
		return $this->findOneBy(['uuid' => $uuid]);
	}

}
