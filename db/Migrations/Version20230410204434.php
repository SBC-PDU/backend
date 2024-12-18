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
 * Adds TOTP support
 */
final class Version20230410204434 extends AbstractMigration {

	/**
	 * Returns the migration description
	 * @return string Description
	 */
	public function getDescription(): string {
		return 'Adds TOTP support';
	}

	/**
	 * Applies the migration
	 * @param Schema $schema Database schema
	 */
	public function up(Schema $schema): void {
		$this->addSql('CREATE TABLE user_totp (uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', user INT DEFAULT NULL, secret VARCHAR(32) NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_E745E5CD5CA2E8E5 (secret), UNIQUE INDEX UNIQ_E745E5CD5E237E06 (name), INDEX IDX_E745E5CD8D93D649 (user), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('ALTER TABLE user_totp ADD CONSTRAINT FK_E745E5CD8D93D649 FOREIGN KEY (user) REFERENCES users (id) ON DELETE CASCADE');
	}

	/**
	 * Reverts the migration
	 * @param Schema $schema Database schema
	 */
	public function down(Schema $schema): void {
		$this->addSql('ALTER TABLE user_totp DROP FOREIGN KEY FK_E745E5CD8D93D649');
		$this->addSql('DROP TABLE user_totp');
	}

}
