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

namespace App\Models\Mail\Senders;

use App\Models\Database\Entities\UserTotp;
use Nette\Mail\SendException;

/**
 * TOTP 2FA mail sender
 */
class TotpMailSender extends BaseMailSender {

	/**
	 * Sends TOTP token added notification
	 * @param UserTotp $totp Added TOTP token
	 * @throws SendException Mail sending error
	 */
	public function sendTotpAdded(UserTotp $totp): void {
		$params = [
			'totp' => $totp,
		];
		$this->sendMessage('totpAdded.latte', $params, $totp->user);
	}

	/**
	 * Sends TOTP token deleted notification
	 * @param UserTotp $totp Deleted TOTP token
	 * @throws SendException Mail sending error
	 */
	public function sendTotpDeleted(UserTotp $totp): void {
		$params = [
			'totp' => $totp,
		];
		$this->sendMessage('totpDeleted.latte', $params, $totp->user);
	}

}
