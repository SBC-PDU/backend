<?php

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

declare(strict_types = 1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodeQuality\Rector\Identical\FlipTypeControlToUseExclusiveTypeRector;
use Rector\CodeQuality\Rector\If_\SimplifyIfElseToTernaryRector;
use Rector\CodingStyle\Rector\Assign\SplitDoubleAssignRector;
use Rector\CodingStyle\Rector\Catch_\CatchExceptionNameMatchingTypeRector;
use Rector\CodingStyle\Rector\ClassMethod\NewlineBeforeNewAssignSetRector;
use Rector\CodingStyle\Rector\Stmt\NewlineAfterStatementRector;
use Rector\CodingStyle\Rector\String_\SymplifyQuoteEscapeRector;
use Rector\Config\RectorConfig;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Transform\Rector\Attribute\AttributeKeyToClassConstFetchRector;

return RectorConfig::configure()
	->withPaths([
		__DIR__ . '/app',
		__DIR__ . '/bin',
		__DIR__ . '/tests',
	])
	->withSkip([
		AttributeKeyToClassConstFetchRector::class,
		CatchExceptionNameMatchingTypeRector::class,
		FlipTypeControlToUseExclusiveTypeRector::class,
		NewlineAfterStatementRector::class,
		NewlineBeforeNewAssignSetRector::class,
		SimplifyIfElseToTernaryRector::class,
		SplitDoubleAssignRector::class,
		SymplifyQuoteEscapeRector::class,
		__DIR__ . '/tests/tmp',
	])
	->withPHPStanConfigs([
		__DIR__ . '/phpstan.neon',
	])
	->withPhpVersion(80300)
	->withPhpSets(php83: true)
	->withPreparedSets(
		deadCode: true,
		codeQuality: true,
		codingStyle: true,
		typeDeclarations: true,
		instanceOf: true,
		strictBooleans: true,
		doctrineCodeQuality: true,
		symfonyCodeQuality: true,
	)
	->withSets([
		DoctrineSetList::DOCTRINE_CODE_QUALITY,
		DoctrineSetList::DOCTRINE_COMMON_20,
		DoctrineSetList::DOCTRINE_DBAL_40,
		DoctrineSetList::DOCTRINE_ORM_214,
		SymfonySetList::SYMFONY_72,
		SymfonySetList::SYMFONY_CODE_QUALITY,
	])
	->withRules([
		InlineConstructorDefaultToPropertyRector::class,
	])
	->withIndent("\t");
