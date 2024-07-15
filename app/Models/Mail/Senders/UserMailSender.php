<?php

declare(strict_types = 1);

/**
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

namespace App\Models\Mail\Senders;

use App\Models\Database\Entities\PasswordRecovery;
use App\Models\Database\Entities\User;
use App\Models\Database\Entities\UserInvitation;
use App\Models\Database\Entities\UserVerification;
use InvalidArgumentException;
use Nette\Mail\SendException;

/**
 * User mail sender
 */
class UserMailSender extends BaseMailSender {

	/**
	 * Sends verification e-mail user
	 * @param UserVerification $verification User verification
	 * @param string $baseUrl Base URL
	 * @throws SendException
	 */
	public function sendVerification(UserVerification $verification, string $baseUrl = ''): void {
		$user = $verification->user;
		$uuid = $verification->getUuid();
		if ($uuid === null) {
			throw new InvalidArgumentException('Verification UUID cannot be null');
		}
		$params = [
			'url' => $baseUrl . '/account/verification/' . $uuid->toString(),
		];
		$this->sendMessage('accountVerification.latte', $params, $user);
	}

	/**
	 * Sends password changed notification
	 * @param User $user User with changed password
	 */
	public function sendPasswordChanged(User $user): void {
		$this->sendMessage('passwordChanged.latte', [], $user);
	}

	/**
	 * Sends initial password set e-mail
	 * @param UserInvitation $invitation User invitation
	 * @param string $baseUrl Base URL
	 * @throws SendException
	 */
	public function sendPasswordSet(UserInvitation $invitation, string $baseUrl = ''): void {
		$uuid = $invitation->getUuid();
		if ($uuid === null) {
			throw new InvalidArgumentException('Invitation UUID cannot be null');
		}
		$params = [
			'url' => $baseUrl . '/auth/password/set/' . $uuid->toString(),
		];
		$this->sendMessage('passwordSet.latte', $params, $invitation->user);
	}

	/**
	 * Sends forgotten password recovery e-mail
	 * @param PasswordRecovery $recovery Forgotten password recovery request
	 * @param string $baseUrl Base URL
	 * @throws SendException
	 */
	public function sendPasswordRecovery(PasswordRecovery $recovery, string $baseUrl = ''): void {
		$uuid = $recovery->getUuid();
		if ($uuid === null) {
			throw new InvalidArgumentException('Password recovery UUID cannot be null');
		}
		$params = [
			'url' => $baseUrl . '/auth/password/reset/' . $uuid->toString(),
		];
		$this->sendMessage('passwordRecovery.latte', $params, $recovery->user);
	}

}
