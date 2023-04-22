<?php

/**
 * TEST: App\Models\Database\Entities\User
 * @covers App\Models\Database\Entities\User
 * @phpVersion >= 8.1
 * @testCase
 *
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

declare(strict_types = 1);

namespace Tests\Unit\Models\Database\Entities;

use App\Exceptions\InvalidEmailAddressException;
use App\Exceptions\InvalidPasswordException;
use App\Models\Database\Entities\User;
use App\Models\Database\Entities\UserTotp;
use App\Models\Database\Enums\AccountState;
use App\Models\Database\Enums\UserLanguage;
use App\Models\Database\Enums\UserRole;
use DateTime;
use OTPHP\TOTP;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../../bootstrap.php';

/**
 * Tests for user database entity
 */
final class UserTest extends TestCase {

	/**
	 * User name
	 */
	private const USERNAME = 'admin';

	/**
	 * E-mail address
	 */
	private const EMAIL = 'admin@romanondracek.cz';

	/**
	 * Password
	 */
	private const PASSWORD = 'password';

	/**
	 * User role
	 */
	private const ROLE = UserRole::Admin;

	/**
	 * User language
	 */
	private const LANGUAGE = UserLanguage::English;

	/**
	 * User account state
	 */
	private const STATE = AccountState::Unverified;

	/**
	 * User created at
	 */
	private const CREATED_AT = '2023-01-01T00:00:00Z';

	/**
	 * TOTP secret
	 */
	private const TOTP_SECRET = 'JDDK4U6G3BJLEZ7Y';

	/**
	 * @var User User entity
	 */
	private User $entity;

	/**
	 * @var UserTotp User TOTP entity
	 */
	private UserTotp $totpEntity;

	/**
	 * Tests the method to create a new user entity from JSON
	 */
	public function testCreateFromJson(): void {
		$this->entity = User::createFromJson([
			'name' => self::USERNAME,
			'email' => self::EMAIL,
			'password' => self::PASSWORD,
			'role' => self::ROLE->value,
			'language' => self::LANGUAGE->value,
		]);
		Assert::same(self::USERNAME, $this->entity->name);
		Assert::same(self::EMAIL, $this->entity->getEmail());
		Assert::false($this->entity->isInvited());
		Assert::true($this->entity->verifyPassword(self::PASSWORD));
		Assert::same(self::ROLE, $this->entity->role);
		Assert::same(self::LANGUAGE, $this->entity->language);
		Assert::same(self::STATE, $this->entity->state);
	}

	/**
	 * Tests the method to get the user's ID
	 */
	public function testGetId(): void {
		Assert::null($this->entity->getId());
	}

	/**
	 * Tests the method to get the user's email address
	 */
	public function testGetEmail(): void {
		Assert::same(self::EMAIL, $this->entity->getEmail());
	}

	/**
	 * Tests the method to set the user's email address (valid e-mail address)
	 */
	public function testSetEmailValid(): void {
		$email = 'test@romanondracek.cz';
		Assert::false($this->entity->hasChangedEmail());
		$this->entity->setEmail($email);
		Assert::same($email, $this->entity->getEmail());
		Assert::true($this->entity->hasChangedEmail());
	}

	/**
	 * Tests the method to set the user's email address (invalid e-mail address)
	 */
	public function testSetEmailInvalid(): void {
		Assert::throws(function (): void {
			$this->entity->setEmail('example.com');
		}, InvalidEmailAddressException::class, 'No domain part found' . PHP_EOL . 'Domain accepts no mail (Null MX, RFC7505)');
	}

	/**
	 * Tests the method to set the user's email address (missing MX DNS record)
	 */
	public function testSetEmailMissingMx(): void {
		Assert::throws(function (): void {
			$this->entity->setEmail('admin@example.com');
		}, InvalidEmailAddressException::class, 'Domain accepts no mail (Null MX, RFC7505)');
	}

	/**
	 * Tests the method to set the user's password
	 */
	public function testSetPassword(): void {
		$password = 'admin';
		Assert::false($this->entity->hasChangedPassword());
		$this->entity->setPassword($password);
		Assert::true($this->entity->verifyPassword($password));
		Assert::true($this->entity->hasChangedPassword());
	}

	/**
	 * Tests the method to set the user's password (new password is the same as the old one)
	 */
	public function testSetPasswordSame(): void {
		Assert::false($this->entity->hasChangedPassword());
		$this->entity->setPassword(self::PASSWORD);
		Assert::true($this->entity->verifyPassword(self::PASSWORD));
		Assert::false($this->entity->hasChangedPassword());
	}

	/**
	 * Tests the method to set the user's password (empty string)
	 */
	public function testSetPasswordEmptyString(): void {
		Assert::throws(function (): void {
			$this->entity->setPassword('');
		}, InvalidPasswordException::class);
	}

	/**
	 * Tests the methods to check if the user has 2FA enabled, add, delete and verify TOTP code
	 */
	public function testTotp(): void {
		Assert::false($this->entity->has2Fa());
		$this->entity->addTotp($this->totpEntity);
		Assert::true($this->entity->has2Fa());
		Assert::true($this->entity->verifyTotpCode(TOTP::createFromSecret(self::TOTP_SECRET)->now()));
		Assert::false($this->entity->verifyTotpCode('123456'));
		$this->entity->deleteTotp($this->totpEntity);
		Assert::false($this->entity->has2Fa());
		Assert::false($this->entity->verifyTotpCode(TOTP::createFromSecret(self::TOTP_SECRET)->now()));
	}

	/**
	 * Tests the method to verify the user's password
	 */
	public function testVerifyPassword(): void {
		Assert::true($this->entity->verifyPassword(self::PASSWORD));
		Assert::false($this->entity->verifyPassword('admin'));
	}

	/**
	 * Tests the method to return JSON serialized entity
	 */
	public function testJsonSerialize(): void {
		$expected = [
			'id' => null,
			'name' => self::USERNAME,
			'email' => self::EMAIL,
			'role' => self::ROLE->value,
			'language' => self::LANGUAGE->value,
			'state' => self::STATE->toString(),
			'createdAt' => self::CREATED_AT,
			'has2Fa' => false,
		];
		Assert::same($expected, $this->entity->jsonSerialize());
	}

	/**
	 * Sets up the test environment
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->entity = new User(self::USERNAME, self::EMAIL, self::PASSWORD, self::ROLE, self::LANGUAGE);
		$this->entity->setCreatedAt(new DateTime(self::CREATED_AT));
		$this->totpEntity = new UserTotp($this->entity, self::TOTP_SECRET, 'TOTP authenticator');
	}

}

$test = new UserTest();
$test->run();
