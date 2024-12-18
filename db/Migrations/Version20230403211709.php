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

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds created_at column to users table
 */
final class Version20230403211709 extends AbstractMigration {

	/**
	 * Returns the migration description
	 * @return string Description
	 */
	public function getDescription(): string {
		return 'Adds created_at column to users table';
	}

	/**
	 * Applies the migration
	 * @param Schema $schema Database schema
	 */
	public function up(Schema $schema): void {
		$this->addSql('ALTER TABLE users ADD created_at DATETIME NOT NULL');
	}

	/**
	 * Reverts the migration
	 * @param Schema $schema Database schema
	 */
	public function down(Schema $schema): void {
		$this->addSql('ALTER TABLE users DROP created_at');
	}

}
