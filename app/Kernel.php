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

namespace App;

use Nette\Bootstrap\Configurator;
use Nette\Utils\Finder;
use SplFileInfo;

/**
 * Application's kernel
 */
class Kernel {

	/**
	 * Boots the application's kernel
	 * @return Configurator Configurator
	 */
	public static function boot(): Configurator {
		$configurator = new Configurator();
		$configurator->setDebugMode(true);
		$configurator->enableTracy(__DIR__ . '/../log');
		$configurator->setTimeZone('Europe/Prague');
		$tempDir = __DIR__ . '/../temp';
		$configurator->setTempDirectory($tempDir);
		$configurator->createRobotLoader()->addDirectory(__DIR__)->register();
		$confDir = __DIR__ . '/config';
		$configurator->addConfig($confDir . '/common.neon');
		$configurator->addStaticParameters(['confDir' => $confDir]);
		/**
		 * @var SplFileInfo $file File info object
		 */
		foreach (Finder::findFiles('*Module/config/config.neon')->from(__DIR__) as $file) {
			$configurator->addConfig($file->getRealPath());
		}
		return $configurator;
	}

}
