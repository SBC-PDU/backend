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

namespace App\Models\Database\Entities;

use App\Exceptions\IncorrectPasswordException;
use App\Exceptions\InvalidEmailAddressException;
use App\Exceptions\InvalidPasswordException;
use App\Exceptions\InvalidUserLanguageException;
use App\Exceptions\InvalidUserRoleException;
use App\Models\Database\Attributes\TCreatedAt;
use App\Models\Database\Attributes\TId;
use App\Models\Database\Enums\AccountState;
use App\Models\Database\Enums\UserLanguage;
use App\Models\Database\Enums\UserRole;
use App\Models\Database\Repositories\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use JsonSerializable;
use ValueError;
use function in_array;
use function password_hash;
use function password_verify;
use const PASSWORD_DEFAULT;

/**
 * User entity
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
class User implements JsonSerializable {

	use TCreatedAt;
	use TId;

	/**
	 * @var PasswordRecovery|null Password recovery
	 */
	#[ORM\OneToOne(mappedBy: 'user', targetEntity: PasswordRecovery::class, cascade: ['persist', 'refresh', 'remove'], orphanRemoval: true)]
	public ?PasswordRecovery $passwordRecovery = null;

	/**
	 * @var UserInvitation|null User invitation
	 */
	#[ORM\OneToOne(mappedBy: 'user', targetEntity: UserInvitation::class, cascade: ['persist', 'refresh', 'remove'], orphanRemoval: true)]
	public ?UserInvitation $invitation = null;

	/**
	 * @var UserVerification|null User verification
	 */
	#[ORM\OneToOne(mappedBy: 'user', targetEntity: UserVerification::class, cascade: ['persist', 'refresh', 'remove'], orphanRemoval: true)]
	public ?UserVerification $verification = null;

	/**
	 * @var string User's email
	 */
	#[ORM\Column(type: 'string', length: 255, unique: true)]
	private string $email;

	/**
	 * @var bool Email changed
	 */
	private bool $emailChanged = false;

	/**
	 * @var bool Password changed
	 */
	private bool $passwordChanged = false;

	/**
	 * @var string|null Password hash
	 */
	#[ORM\Column(type: 'string', length: 255, nullable: true)]
	private ?string $password = null;

	/**
	 * @var Collection<int, UserTotp> User TOTP tokens
	 */
	#[ORM\OneToMany(mappedBy: 'user', targetEntity: UserTotp::class, cascade: ['persist', 'refresh', 'remove'], orphanRemoval: true)]
	private Collection $totp;

	/**
	 * Constructor
	 * @param string $name User name
	 * @param string $email User's email
	 * @param string|null $password User password
	 * @param UserRole $role User role
	 * @param UserLanguage $language User language
	 * @param AccountState $state Account state
	 */
	public function __construct(
		#[ORM\Column(type: 'string', length: 255)]
		public string $name,
		string $email,
		?string $password,
		#[ORM\Column(type: 'string', length: 15, enumType: UserRole::class)]
		public UserRole $role = UserRole::Normal,
		#[ORM\Column(type: 'string', length: 7, enumType: UserLanguage::class, options: ['default' => UserLanguage::Default])]
		public UserLanguage $language = UserLanguage::Default,
		#[ORM\Column(enumType: AccountState::class, options: ['default' => AccountState::Default])]
		public AccountState $state = AccountState::Default,
	) {
		$this->setEmail($email);
		if ($password === null) {
			$this->password = null;
		} else {
			$this->setPassword($password);
		}
		$this->emailChanged = false;
		$this->passwordChanged = false;
		$this->totp = new ArrayCollection();
	}

	/**
	 * Creates a new user from JSON data
	 * @param array{name: string, email:string, password?: string, role?:string, language?: string} $json JSON data
	 * @return self User entity
	 */
	public static function createFromJson(array $json): self {
		$password = $json['password'] ?? null;
		return new self(
			$json['name'],
			$json['email'],
			$password,
			UserRole::tryFrom($json['role'] ?? null) ?? UserRole::Default,
			UserLanguage::tryFrom($json['language'] ?? null) ?? UserLanguage::Default,
			$password === null ? AccountState::Invited : AccountState::Default,
		);
	}

	/**
	 * Edit user from JSON data
	 * @param array{name: string, email: string, role?: string, language?: string} $json JSON data
	 * @throws InvalidEmailAddressException Invalid email address
	 * @throws InvalidUserLanguageException Invalid user language
	 * @throws InvalidUserRoleException Invalid user role
	 */
	public function editFromJson(array $json): void {
		$this->name = $json['name'];
		$this->setEmail($json['email']);
		if (array_key_exists('language', $json)) {
			try {
				$this->language = UserLanguage::from($json['language']);
			} catch (ValueError $e) {
				throw new InvalidUserLanguageException('Invalid language', previous: $e);
			}
		}
		if (array_key_exists('role', $json)) {
			try {
				$this->role = UserRole::from($json['role']);
			} catch (ValueError $e) {
				throw new InvalidUserRoleException('Invalid role', previous: $e);
			}
		}
	}

	/**
	 * Returns the user's email
	 * @return string User's email
	 */
	public function getEmail(): string {
		return $this->email;
	}

	/**
	 * Changes the user's password
	 * @param string $oldPassword Current password
	 * @param string $newPassword New password to set
	 * @throws IncorrectPasswordException Incorrect current password
	 * @throws InvalidPasswordException Invalid new password
	 */
	public function changePassword(string $oldPassword, string $newPassword): void {
		if (!$this->verifyPassword($oldPassword)) {
			throw new IncorrectPasswordException('Incorrect current password.');
		}
		$this->setPassword($newPassword);
	}

	/**
	 * Returns all user scopes
	 * @return array<string> User scopes
	 */
	public function getScopes(): array {
		$scopes = ['normal'];
		if ($this->role === UserRole::Admin) {
			$scopes = array_merge($scopes, ['admin']);
		}
		return $scopes;
	}

	/**
	 * Checks if the user has changed e-mail
	 * @return bool User has changed e-mail
	 */
	public function hasChangedEmail(): bool {
		return $this->emailChanged;
	}

	/**
	 * Checks if the user has changed password
	 * @return bool User has changed password
	 */
	public function hasChangedPassword(): bool {
		return $this->passwordChanged;
	}

	/**
	 * Checks if the user has a scope
	 * @param string $scope Scope
	 * @return bool User has a scope
	 */
	public function hasScope(string $scope): bool {
		return in_array($scope, $this->getScopes(), true);
	}

	/**
	 * Sets the user's email
	 * @param string $email User's email
	 * @throws InvalidEmailAddressException
	 */
	public function setEmail(string $email): void {
		$this->validateEmail($email);
		if (!isset($this->email) || $this->email !== $email) {
			$this->state = $this->state->isUnverified() ? $this->state : $this->state->unverify();
			$this->emailChanged = true;
		}
		$this->email = $email;
	}

	/**
	 * Checks if the user is invited for initial password setup
	 * @return bool Is the user invited?
	 */
	public function isInvited(): bool {
		return $this->password === null;
	}

	/**
	 * Sets the user's password
	 * @param string $password User's password
	 * @throws InvalidPasswordException Invalid password
	 */
	public function setPassword(string $password): void {
		if ($password === '') {
			throw new InvalidPasswordException('Empty new password.');
		}
		$this->passwordChanged = !$this->verifyPassword($password);
		$this->password = password_hash($password, PASSWORD_DEFAULT);
	}

	/**
	 * Adds a TOTP to the user
	 * @param UserTotp $totp TOTP to add
	 */
	public function addTotp(UserTotp $totp): void {
		$this->totp->add($totp);
	}

	/**
	 * Deletes a TOTP from the user
	 * @param UserTotp $totp TOTP to delete
	 */
	public function deleteTotp(UserTotp $totp): void {
		$this->totp->removeElement($totp);
	}

	/**
	 * Checks if the user has 2FA enabled
	 * @return bool Does the user have 2FA enabled?
	 */
	public function has2Fa(): bool {
		return $this->totp->count() !== 0;
	}

	/**
	 * Verifies the TOTP code
	 * @param string $code TOTP code to verify
	 * @return bool Is the TOTP code correct?
	 */
	public function verifyTotpCode(string $code): bool {
		foreach ($this->totp as $totp) {
			if ($totp->verify($code)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Verifies the password
	 * @param string $password Password to verify
	 * @return bool Is the password correct?
	 */
	public function verifyPassword(string $password): bool {
		if ($this->password === null) {
			return false;
		}
		return password_verify($password, $this->password);
	}

	/**
	 * Returns the JSON serialized User entity
	 * @return array<string, bool|int|string|null> JSON serialized User entity
	 */
	public function jsonSerialize(): array {
		return [
			'id' => $this->id ?? null,
			'name' => $this->name,
			'email' => $this->email,
			'role' => $this->role->value,
			'language' => $this->language->value,
			'state' => $this->state->toString(),
			'createdAt' => $this->createdAt->format('Y-m-d\TH:i:sp'),
			'has2Fa' => $this->has2Fa(),
		];
	}

	/**
	 * Validates e-mail address
	 * @param string $email E-mail address to validate
	 */
	private function validateEmail(string $email): void {
		$validator = new EmailValidator();
		$validationRules = [
			new RFCValidation(),
		];
		if (function_exists('dns_get_record')) {
			$validationRules[] = new DNSCheckValidation();
		}
		if (!$validator->isValid($email, new MultipleValidationWithAnd($validationRules))) {
			$error = $validator->getError();
			if ($error === null) {
				throw new InvalidEmailAddressException();
			}
			throw new InvalidEmailAddressException($error->description(), $error->code());
		}
	}

}
