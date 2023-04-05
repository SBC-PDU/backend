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

namespace App\Models\Database;

use App\Models\Database\Entities\Device;
use App\Models\Database\Entities\DeviceOutput;
use App\Models\Database\Entities\PasswordRecovery;
use App\Models\Database\Entities\User;
use App\Models\Database\Entities\UserInvitation;
use App\Models\Database\Entities\UserVerification;
use App\Models\Database\Repositories\DeviceOutputRepository;
use App\Models\Database\Repositories\DeviceRepository;
use App\Models\Database\Repositories\PasswordRecoveryRepository;
use App\Models\Database\Repositories\UserInvitationRepository;
use App\Models\Database\Repositories\UserRepository;
use App\Models\Database\Repositories\UserVerificationRepository;

/**
 * @mixin EntityManager
 */
trait TRepositories {

	/**
	 * Returns the device repository
	 * @return DeviceRepository Device repository
	 */
	public function getDeviceRepository(): DeviceRepository {
		return $this->getRepository(Device::class);
	}

	/**
	 * Returns the device output repository
	 * @return DeviceOutputRepository Device output repository
	 */
	public function getDeviceOutputRepository(): DeviceOutputRepository {
		return $this->getRepository(DeviceOutput::class);
	}

	/**
	 * Returns the password recovery repository
	 * @return PasswordRecoveryRepository Password recovery repository
	 */
	public function getPasswordRecoveryRepository(): PasswordRecoveryRepository {
		return $this->getRepository(PasswordRecovery::class);
	}

	/**
	 * Returns the user invitation repository
	 * @return UserInvitationRepository User invitation repository
	 */
	public function getUserInvitationRepository(): UserInvitationRepository {
		return $this->getRepository(UserInvitation::class);
	}

	/**
	 * Returns the user repository
	 * @return UserRepository User repository
	 */
	public function getUserRepository(): UserRepository {
		return $this->getRepository(User::class);
	}

	/**
	 * Returns the user verification repository
	 * @return UserVerificationRepository User verification repository
	 */
	public function getUserVerificationRepository(): UserVerificationRepository {
		return $this->getRepository(UserVerification::class);
	}

}
