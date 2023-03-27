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

namespace Database\Fixtures;

use App\Models\Database\Entities\User;
use App\Models\Database\Enums\AccountState;
use App\Models\Database\Enums\UserLanguage;
use App\Models\Database\Enums\UserRole;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Initial user fixture
 */
class UsersFixture implements FixtureInterface {

	/**
	 * @inheritDoc
	 */
	public function load(ObjectManager $manager) {
		$users = [
			new User('Admin', 'admin@romanondracek.cz', 'admin', UserRole::Admin, UserLanguage::English, AccountState::Verified),
		];
		foreach ($users as $user) {
			$manager->persist($user);
		}
		$manager->flush();
	}
}
