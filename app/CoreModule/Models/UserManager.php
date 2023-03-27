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

namespace App\CoreModule\Models;

use App\ApiModule\Version1\Models\JwtConfigurator;
use App\Models\Database\Entities\PasswordRecovery;
use App\Models\Database\Entities\User;
use App\Models\Database\Entities\UserVerification;
use App\Models\Database\EntityManager;
use App\Models\Database\Enums\AccountState;
use App\Models\Database\Enums\UserRole;
use App\Models\Database\Repositories\UserRepository;
use App\Models\Mail\Senders\EmailVerificationMailSender;
use App\Models\Mail\Senders\PasswordRecoveryMailSender;
use BadMethodCallException;
use DateTimeImmutable;
use Nette\Mail\SendException;

/**
 * User manager
 */
class UserManager {

	/**
	 * @var UserRepository User database repository
	 */
	private readonly UserRepository $repository;

	/**
	 * Constructor
	 * @param EntityManager $entityManager Entity manager
	 * @param EmailVerificationMailSender $emailVerificationSender Email verification sender
	 */
	public function __construct(
		private readonly EntityManager $entityManager,
		private readonly JwtConfigurator $jwtConfigurator,
		private readonly EmailVerificationMailSender $emailVerificationSender,
		private readonly PasswordRecoveryMailSender $passwordRecoverySender,
	) {
		$this->repository = $entityManager->getUserRepository();
	}

	/**
	 * Checks the e-mail address uniqueness
	 * @param string $email E-mail address
	 * @param int|null $userId User ID
	 * @return bool E-mail address uniqueness
	 */
	public function checkEmailUniqueness(string $email, ?int $userId = null): bool {
		$user = $this->entityManager->getUserRepository()->findOneByEmail($email);
		return $user !== null && $user->getId() !== $userId;
	}

	/**
	 * Creates a new user
	 * @param User $user User to be created
	 */
	public function create(User $user): void {
		$this->entityManager->persist($user);
		$this->entityManager->flush();
	}

	/**
	 * Creates a new JWT token for the user
	 * @param User $user User
	 * @return string Generated JWT
	 */
	public function createJwt(User $user): string {
		$now = new DateTimeImmutable();
		$us = $now->format('u');
		$now = $now->modify('-' . $us . ' usec');
		$configuration = $this->jwtConfigurator->create();
		$builder = $configuration->builder()
			->issuedAt($now)
			->expiresAt($now->modify('+90 min'))
			->withClaim('uid', $user->getId());
		$signer = $configuration->signer();
		$signingKey = $configuration->signingKey();
		return $builder->getToken($signer, $signingKey)->toString();
	}

	/**
	 * Create password recovery request
	 * @param User $user User
	 * @param string $baseUrl Frontend base URL
	 * @throws BadMethodCallException User's e-mail address is not verified.
	 * @throws SendException Failed to send e-mail message
	 */
	public function createPasswordRecoveryRequest(User $user, string $baseUrl): void {
		if ($user->state !== AccountState::Verified) {
			throw new BadMethodCallException('E-mail address is not verified');
		}
		$recovery = new PasswordRecovery($user);
		$this->entityManager->persist($recovery);
		$this->passwordRecoverySender->send($recovery, $baseUrl);
		$this->entityManager->flush();
	}

	/**
	 * Deletes an user
	 * @param User $user User to delete
	 */
	public function delete(User $user): void {
		if (($user->role === UserRole::Admin) &&
			($this->repository->userCountByRole(UserRole::Admin) === 1)) {
			throw new BadMethodCallException('Admin user deletion forbidden for the only admin user');
		}
		$this->entityManager->remove($user);
		$this->entityManager->flush();
	}

	/**
	 * Finds user by e-mail address
	 * @param string $email E-mail address
	 * @return User|null User
	 */
	public function findByEmail(string $email): ?User {
		return $this->repository->findOneByEmail($email);
	}

	/**
	 * Lists all users
	 * @param array<string> $roles User roles to filter
	 * @return array<User> Users
	 */
	public function list(array $roles = []): array {
		$criteria = $roles === [] ? [] : ['role' => $roles];
		return $this->repository->findBy($criteria);
	}

	/**
	 * Blocks user
	 * @param User $user User to block
	 */
	public function block(User $user): void {
		if ($user->state->isBlocked()) {
			throw new BadMethodCallException('User is already blocked');
		}
		$user->state = $user->state === AccountState::Unverified ? AccountState::BlockedUnverified : AccountState::BlockedVerified;
		$this->entityManager->persist($user);
		$this->entityManager->flush();
	}

	/**
	 * Unblocks user
	 * @param User $user User to unblock
	 */
	public function unblock(User $user): void {
		if (!$user->state->isBlocked()) {
			throw new BadMethodCallException('User is not blocked');
		}
		$user->state = $user->state === AccountState::BlockedUnverified ? AccountState::Unverified : AccountState::Verified;
		$this->entityManager->persist($user);
		$this->entityManager->flush();
	}

	/**
	 * Sends user verification e-mail
	 * @param User $user User
	 * @param string $baseUrl Frontend base URL
	 * @throws SendException
	 */
	public function sendVerificationEmail(User $user, string $baseUrl): void {
		$verification = new UserVerification($user);
		$this->entityManager->persist($verification);
		$this->entityManager->flush();
		$this->emailVerificationSender->send($verification, $baseUrl);
	}

}
