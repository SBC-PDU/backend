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

use App\Exceptions\IncorrectPasswordException;
use App\Exceptions\IncorrectTotpCodeException;
use App\Exceptions\ResourceNotFoundException;
use App\Models\Database\Entities\User;
use App\Models\Database\Entities\UserTotp;
use App\Models\Database\EntityManager;
use App\Models\Database\Repositories\UserTotpRepository;
use App\Models\Mail\Senders\TotpMailSender;
use ValueError;

/**
 * TOTP manager
 */
class TotpManager {

	/**
	 * @var UserTotpRepository TOTP database repository
	 */
	private readonly UserTotpRepository $repository;

	/**
	 * Constructor
	 * @param EntityManager $entityManager Entity manager
	 * @param TotpMailSender $mailSender TOTP mail sender
	 */
	public function __construct(
		private readonly EntityManager $entityManager,
		private readonly TotpMailSender $mailSender,
	) {
		$this->repository = $this->entityManager->getUserTotpRepository();
	}

	/**
	 * Lists TOTP tokens for the user
	 * @param User $user User
	 * @return array<UserTotp> TOTP tokens
	 */
	public function list(User $user): array {
		return $this->repository->findByUser($user);
	}

	/**
	 * Registers a new TOTP token for the user
	 * @param User $user User
	 * @param array{name: string, secret: string, code: string, password: string} $json JSON
	 * @return UserTotp Created TOTP token
	 * @throws IncorrectPasswordException Incorrect password
	 * @throws IncorrectTotpCodeException Incorrect TOTP code
	 * @throws ValueError Secret cannot be empty
	 */
	public function add(User $user, array $json): UserTotp {
		$secret = $json['secret'];
		if ($secret === '') {
			throw new ValueError('Secret cannot be empty');
		}
		$totp = new UserTotp($user, $secret, $json['name']);
		if (!$totp->verify($json['code'])) {
			throw new IncorrectTotpCodeException('Incorrect code');
		}
		if (!$user->verifyPassword($json['password'])) {
			throw new IncorrectPasswordException('Incorrect password');
		}
		$this->entityManager->persist($totp);
		$this->entityManager->flush();
		$this->mailSender->sendTotpAdded($totp);
		return $totp;
	}

	/**
	 * Returns a TOTP token for the user
	 * @param User $user User
	 * @param string $uuid TOTP token UUID
	 * @return UserTotp TOTP token
	 * @throws ResourceNotFoundException TOTP token not found
	 */
	public function get(User $user, string $uuid): UserTotp {
		$totp = $this->repository->findOneByUuid($uuid);
		if ($totp === null || $totp->user->getId() !== $user->getId()) {
			throw new ResourceNotFoundException('TOTP token not found');
		}
		return $totp;
	}

	/**
	 * Deletes a TOTP token for the user
	 * @param User $user User
	 * @param string $uuid TOTP token UUID for deletion
	 * @param string $code TOTP code for verification
	 * @param string $password Password for verification
	 * @throws ResourceNotFoundException TOTP token not found
	 * @throws IncorrectPasswordException Incorrect password
	 * @throws IncorrectTotpCodeException Incorrect TOTP code
	 */
	public function delete(User $user, string $uuid, string $code, string $password): void {
		$totp = $this->repository->findOneByUuid($uuid);
		if ($totp === null || $totp->user->getId() !== $user->getId()) {
			throw new ResourceNotFoundException('TOTP token not found');
		}
		if (!$user->verifyPassword($password)) {
			throw new IncorrectPasswordException('Incorrect password');
		}
		if (!$user->verifyTotpCode($code)) {
			throw new IncorrectTotpCodeException('Incorrect TOTP code');
		}
		$this->entityManager->remove($totp);
		$this->entityManager->flush();
		$this->mailSender->sendTotpDeleted($totp);
	}

}
