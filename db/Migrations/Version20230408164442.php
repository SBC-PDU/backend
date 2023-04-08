<?php

declare(strict_types=1);

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

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fixes account invitations, account verification and password recovery
 */
final class Version20230408164442 extends AbstractMigration {

	/**
	 * Returns the migration description
	 * @return string Description
	 */
	public function getDescription(): string {
		return 'Fixes account invitations, account verification and password recovery';
	}

	public function up(Schema $schema): void {
		// this up() migration is auto-generated, please modify it to your needs
		$this->addSql('CREATE TABLE user_verification (uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', user INT DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_DA3DB9098D93D649 (user), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('ALTER TABLE user_verification ADD CONSTRAINT FK_DA3DB9098D93D649 FOREIGN KEY (user) REFERENCES users (id) ON DELETE CASCADE');
		$this->addSql('ALTER TABLE email_verification DROP FOREIGN KEY FK_FE223588D93D649');
		$this->addSql('DROP TABLE email_verification');
		$this->addSql('ALTER TABLE password_recovery DROP INDEX IDX_63D401098D93D649, ADD UNIQUE INDEX UNIQ_63D401098D93D649 (user)');
	}

	public function down(Schema $schema): void {
		// this down() migration is auto-generated, please modify it to your needs
		$this->addSql('CREATE TABLE email_verification (uuid CHAR(36) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci` COMMENT \'(DC2Type:uuid)\', user INT DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_FE223588D93D649 (user), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
		$this->addSql('ALTER TABLE email_verification ADD CONSTRAINT FK_FE223588D93D649 FOREIGN KEY (user) REFERENCES users (id) ON DELETE CASCADE');
		$this->addSql('ALTER TABLE user_verification DROP FOREIGN KEY FK_DA3DB9098D93D649');
		$this->addSql('DROP TABLE user_verification');
		$this->addSql('ALTER TABLE password_recovery DROP INDEX UNIQ_63D401098D93D649, ADD INDEX IDX_63D401098D93D649 (user)');
	}
}
