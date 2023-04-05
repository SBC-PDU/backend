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
use App\Exceptions\BlockedAccountException;
use App\Exceptions\ConflictedEmailAddressException;
use App\Exceptions\InvalidAccountStateException;
use App\Exceptions\ResourceExpiredException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Database\Entities\PasswordRecovery;
use App\Models\Database\Entities\User;
use App\Models\Database\Entities\UserInvitation;
use App\Models\Database\Entities\UserVerification;
use App\Models\Database\EntityManager;
use App\Models\Database\Enums\UserRole;
use App\Models\Database\Repositories\UserRepository;
use App\Models\Mail\Senders\UserMailSender;
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
	 * @param JwtConfigurator $jwtConfigurator JWT configurator
	 * @param UserMailSender $mailSender User mail sender
	 */
	public function __construct(
		private readonly EntityManager $entityManager,
		private readonly JwtConfigurator $jwtConfigurator,
		private readonly UserMailSender $mailSender,
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
	 * @param string $baseUrl Frontend base URL
	 * @throws ConflictedEmailAddressException E-mail address is already used
	 */
	public function create(User $user, string $baseUrl): void {
		if ($this->checkEmailUniqueness($user->getEmail())) {
			throw new ConflictedEmailAddressException('E-main address is already used');
		}
		$this->entityManager->persist($user);
		$this->entityManager->flush();
		try {
			if ($user->isInvited()) {
				$this->sendInvitationEmail($user, $baseUrl);
			} else {
				$this->sendVerificationEmail($user, $baseUrl);
			}
		} catch (SendException) {
			// Ignore
		}
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
		if (!$user->state->isVerified()) {
			throw new BadMethodCallException('E-mail address is not verified');
		}
		if ($user->passwordRecovery !== null) {
			$this->entityManager->remove($user->passwordRecovery);
		}
		$recovery = new PasswordRecovery($user);
		$user->passwordRecovery = $recovery;
		$this->entityManager->persist($user);
		$this->mailSender->sendPasswordRecovery($recovery, $baseUrl);
		$this->entityManager->flush();
	}

	/**
	 * Deletes an user
	 * @param User $user User to delete
	 */
	public function delete(User $user): void {
		if (($user->role === UserRole::Admin) && $this->hasOnlySingleAdmin()) {
			throw new BadMethodCallException('Admin user deletion forbidden for the only admin user');
		}
		$this->entityManager->remove($user);
		$this->entityManager->flush();
	}

	/**
	 * Edits an user
	 * @param User $user Edited user
	 * @param string $baseUrl Frontend base URL
	 * @throws ConflictedEmailAddressException E-mail address is already used
	 */
	public function edit(User $user, string $baseUrl): void {
		if ($this->checkEmailUniqueness($user->getEmail(), $user->getId())) {
			throw new ConflictedEmailAddressException('E-main address is already used');
		}
		$this->entityManager->persist($user);
		if ($user->hasChangedEmail()) {
			try {
				$this->sendVerificationEmail($user, $baseUrl);
			} catch (SendException) {
				// Ignore
			}
		}
		if ($user->hasChangedPassword()) {
			try {
				$this->mailSender->sendPasswordChanged($user);
			} catch (SendException) {
				// Ignore
			}
		}
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
	 * Returns user by e-mail address
	 * @param string $email E-mail address
	 * @return User User
	 * @throws ResourceNotFoundException User not found
	 */
	public function getByEmail(string $email): User {
		return $this->repository->getByEmail($email);
	}

	/**
	 * Returns user by ID
	 * @param int $id User ID
	 * @return User User
	 * @throws ResourceNotFoundException User not found
	 */
	public function getById(int $id): User {
		return $this->repository->getById($id);
	}

	/**
	 * Checks if there is only a single admin user
	 * @return bool True if there is only a single admin user
	 */
	public function hasOnlySingleAdmin(): bool {
		return $this->repository->userCountByRole(UserRole::Admin) === 1;
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
	 * @throws InvalidAccountStateException User is already blocked
	 */
	public function block(User $user): void {
		$user->state = $user->state->block();
		$this->entityManager->persist($user);
		$this->entityManager->flush();
	}

	/**
	 * Unblocks user
	 * @param User $user User to unblock
	 * @throws InvalidAccountStateException User is already unblocked
	 */
	public function unblock(User $user): void {
		$user->state = $user->state->unblock();
		$this->entityManager->persist($user);
		$this->entityManager->flush();
	}

	/**
	 * Verifies user's e-mail address
	 * @param UserVerification $verification Verification
	 * @throws InvalidAccountStateException User is already verified
	 * @throws ResourceExpiredException Verification expired
	 * @throws BlockedAccountException User is blocked
	 */
	public function verify(UserVerification $verification, string $baseUrl): void {
		$user = $verification->user;
		if ($user->state->isVerified()) {
			throw new InvalidAccountStateException('User is already verified');
		}
		if ($verification->isExpired()) {
			try {
				$this->sendVerificationEmail($user, $baseUrl);
			} catch (SendException) {
				// Ignore failure
			}
			throw new ResourceExpiredException('Verification link expired');
		}
		$user->state = $user->state->verify();
		$this->entityManager->persist($user);
		$this->entityManager->flush();
		if ($user->state->isBlocked()) {
			throw new BlockedAccountException('User is blocked');
		}
	}

	/**
	 * Sends user invitation e-mail
	 * @param User $user User
	 * @param string $baseUrl Frontend base URL
	 * @throws SendException
	 */
	public function sendInvitationEmail(User $user, string $baseUrl): void {
		$repository = $this->entityManager->getUserInvitationRepository();
		$currentInvitation = $repository->findOneBy(['user' => $user]);
		if ($currentInvitation instanceof UserInvitation) {
			$this->entityManager->remove($currentInvitation);
		}
		$invitation = new UserInvitation($user);
		$user->invitation = $invitation;
		$this->entityManager->persist($user);
		$this->entityManager->flush();
		$this->mailSender->sendPasswordSet($invitation, $baseUrl);
	}

	/**
	 * Sends user verification e-mail
	 * @param User $user User
	 * @param string $baseUrl Frontend base URL
	 * @throws InvalidAccountStateException User is already verified
	 * @throws SendException Failed to send e-mail message
	 */
	public function sendVerificationEmail(User $user, string $baseUrl): void {
		if ($user->state->isVerified()) {
			throw new InvalidAccountStateException('User is already verified');
		}
		if ($user->verification instanceof UserVerification) {
			$this->entityManager->remove($user->verification);
		}
		$verification = new UserVerification($user);
		$user->verification = $verification;
		$this->entityManager->persist($user);
		$this->entityManager->flush();
		$this->mailSender->sendVerification($verification, $baseUrl);
	}

}
