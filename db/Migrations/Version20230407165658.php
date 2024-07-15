<?php

declare(strict_types=1);

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

namespace Database\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adds user invitations
 */
final class Version20230407165658 extends AbstractMigration {

	/**
	 * Returns the migration description
	 * @return string Description
	 */
	public function getDescription(): string {
		return 'Adds user invitations';
	}

	/**
	 * Applies the migration
	 * @param Schema $schema Database schema
	 */
	public function up(Schema $schema): void {
		// this up() migration is auto-generated, please modify it to your needs
		$this->addSql('CREATE TABLE user_invitations (uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', user INT DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_8A3CD93B8D93D649 (user), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('ALTER TABLE user_invitations ADD CONSTRAINT FK_8A3CD93B8D93D649 FOREIGN KEY (user) REFERENCES users (id) ON DELETE CASCADE');
		$this->addSql('ALTER TABLE email_verification DROP INDEX IDX_FE223588D93D649, ADD UNIQUE INDEX UNIQ_FE223588D93D649 (user)');
		$this->addSql('ALTER TABLE users CHANGE password password VARCHAR(255) DEFAULT NULL');
	}

	/**
	 * Reverts the migration
	 * @param Schema $schema Database schema
	 */
	public function down(Schema $schema): void {
		// this down() migration is auto-generated, please modify it to your needs
		$this->addSql('ALTER TABLE user_invitations DROP FOREIGN KEY FK_8A3CD93B8D93D649');
		$this->addSql('DROP TABLE user_invitations');
		$this->addSql('ALTER TABLE email_verification DROP INDEX UNIQ_FE223588D93D649, ADD INDEX IDX_FE223588D93D649 (user)');
		$this->addSql('ALTER TABLE users CHANGE password password VARCHAR(255) NOT NULL');
	}

}
