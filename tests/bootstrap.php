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

use App\Kernel;
use Ninjify\Nunjuck\Environment;

require __DIR__ . '/../vendor/autoload.php';

$configurator = Kernel::boot();
$configurator->setDebugMode(false);
$configurator->setTempDirectory(__DIR__ . '/tmp');

Environment::setupTester();
Environment::setupTimezone('Etc/GMT-2');
if (!defined('TESTER_DIR')) {
	Environment::setupVariables(__DIR__);
}
if (basename(__DIR__) === 'tests') {
	$tempDir = __DIR__ . '/tmp/';
	@mkdir($tempDir);
	@mkdir($tempDir . 'certificates/');
	@mkdir($tempDir . 'configuration/');
	@mkdir($tempDir . 'configuration/scheduler/');
	@mkdir($tempDir . 'controller/');
	@mkdir($tempDir . 'maintenance/');
	@mkdir($tempDir . 'translator/');
	@mkdir($tempDir . 'zip/');
}

return $configurator->createContainer();
