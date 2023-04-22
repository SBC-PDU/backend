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

namespace App\ApiModule\Version1\Models;

use App\Exceptions\JwtConfigurationException;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Ecdsa\Sha384 as EcdsaSha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Nette\IOException;
use Nette\Utils\FileSystem;

/**
 * JWT configurator
 */
class JwtConfigurator {

	/**
	 * Creates a JWT configuration
	 * @return Configuration JWT configuration
	 * @throws JwtConfigurationException
	 */
	public function create(): Configuration {
		$signer = new EcdsaSha256();
		$dir = __DIR__ . '/../../../cert';
		try {
			$privateKey = FileSystem::read($dir . '/privkey.pem');
			if ($privateKey === '') {
				throw new JwtConfigurationException('Private key file is empty');
			}
			$signingKey = InMemory::plainText($privateKey);
		} catch (IOException) {
			throw new JwtConfigurationException('Private key file not found');
		}
		try {
			$certificate = FileSystem::read($dir . '/cert.pem');
			if ($certificate === '') {
				throw new JwtConfigurationException('Certificate file is empty');
			}
			$publicKey = openssl_pkey_get_public($certificate);
			if ($publicKey === false) {
				throw new JwtConfigurationException('Certificate file is invalid, reason: ' . openssl_error_string());
			}
		} catch (IOException) {
			throw new JwtConfigurationException('Certificate file not found');
		}
		$publicKeyDetails = openssl_pkey_get_details($publicKey);
		if ($publicKeyDetails === false) {
			throw new JwtConfigurationException('Unable to get details about public key, reason: ' . openssl_error_string());
		}
		$verificationKey = InMemory::plainText($publicKeyDetails['key']);
		return Configuration::forAsymmetricSigner($signer, $signingKey, $verificationKey);
	}

}
