<?php

/**
 * TEST: App\Models\Database\Entities\User
 * @covers App\Models\Database\Entities\User
 * @phpVersion >= 8.1
 * @testCase
 *
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

namespace Tests\Unit\Models\Database\Entities;

use App\Models\Database\Entities\PasswordRecovery;
use App\Models\Database\Entities\User;
use DateInterval;
use DateTime;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../../bootstrap.php';

/**
 * Tests for password recovery request database entity
 */
final class PasswordRecoveryTest extends TestCase {

	/**
	 * Date time format
	 */
	private const string DATETIME_FORMAT = 'Y-m-d H:i:00';

	/**
	 * @var PasswordRecovery Password recovery entity
	 */
	private PasswordRecovery $entity;

	/**
	 * @var User User entity
	 */
	private User $user;

	/**
	 * Tests the function to get the created date
	 */
	public function testGetCreatedAt(): void {
		$now = new DateTime();
		$expected = $now->format(self::DATETIME_FORMAT);
		Assert::equal($expected, $this->entity->getCreatedAt()->format(self::DATETIME_FORMAT));
	}

	/**
	 * Tests the function to set the created date
	 */
	public function testSetCreatedAt(): void {
		$now = new DateTime();
		$this->entity->setCreatedAt($now);
		Assert::same($now, $this->entity->getCreatedAt());
	}

	/**
	 * Tests the function to check if the password recovery request is expired (valid)
	 */
	public function testIsExpiredFalse(): void {
		Assert::false($this->entity->isExpired());
	}

	/**
	 * Tests the function to check if the password recovery request is expired (expired)
	 */
	public function testIsExpiredTrue(): void {
		$now = new DateTime();
		$now->sub(new DateInterval('P1D'));
		$this->entity->setCreatedAt($now);
		Assert::true($this->entity->isExpired());
	}

	/**
	 * Sets up the test environment
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->user = new User('admin', 'admin@romanondracek.cz', 'admin');
		$this->entity = new PasswordRecovery($this->user);
		$this->entity->setCreatedAt();
	}

}

$test = new PasswordRecoveryTest();
$test->run();
