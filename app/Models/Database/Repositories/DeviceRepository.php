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

use App\Models\Database\Entities\Device;
use Doctrine\ORM\EntityRepository;

/**
 * Device repository
 * @extends EntityRepository<Device>
 */
class DeviceRepository extends EntityRepository {

	/**
	 * Finds the device by ID
	 * @param string $id Device ID
	 * @return Device|null Device entity
	 */
	public function findOneById(string $id): ?Device {
		return $this->findOneBy(['id' => $id]);
	}

}
