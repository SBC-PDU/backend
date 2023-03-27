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

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Ecdsa\Sha384 as EcdsaSha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Nette\Utils\FileSystem;

/**
 * JWT configurator
 */
class JwtConfigurator {

	/**
	 * Creates a JWT configuration
	 * @return Configuration JWT configuration
	 */
	public function create(): Configuration {
		$signer = new EcdsaSha256();
		$dir = __DIR__ . '/../../../cert';
		$privateKey = FileSystem::read($dir . '/privkey.pem');
		$signingKey = InMemory::plainText($privateKey);
		$certificate = openssl_pkey_get_public(FileSystem::read($dir . '/cert.pem'));
		$verificationKey = openssl_pkey_get_details($certificate)['key'];
		return Configuration::forAsymmetricSigner($signer, $signingKey, InMemory::plainText($verificationKey));
	}

}
