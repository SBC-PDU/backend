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
 * Initial migration
 */
final class Version20220427150900 extends AbstractMigration {

	/**
	 * Returns the migration description
	 * @return string Migration description
	 */
	public function getDescription(): string {
		return 'Initial migration';
	}

	/**
	 * Applies migration
	 * @param Schema $schema Database schema
	 */
	public function up(Schema $schema): void {
		// this up() migration is auto-generated, please modify it to your needs
		$this->addSql('CREATE TABLE `email_verification` (uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', user INT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_FE223588D93D649 (user), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE `password_recovery` (uuid CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', user INT DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_63D401098D93D649 (user), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE `users` (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, role VARCHAR(15) NOT NULL, state INT DEFAULT 0 NOT NULL, language VARCHAR(7) NOT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('ALTER TABLE `email_verification` ADD CONSTRAINT FK_FE223588D93D649 FOREIGN KEY (user) REFERENCES `users` (id) ON DELETE CASCADE');
		$this->addSql('ALTER TABLE `password_recovery` ADD CONSTRAINT FK_63D401098D93D649 FOREIGN KEY (user) REFERENCES `users` (id) ON DELETE CASCADE');
	}

	/**
	 * Reverts migration
	 * @param Schema $schema Database schema
	 */
	public function down(Schema $schema): void {
		// this down() migration is auto-generated, please modify it to your needs
		$this->addSql('ALTER TABLE `email_verification` DROP FOREIGN KEY FK_FE223588D93D649');
		$this->addSql('ALTER TABLE `password_recovery` DROP FOREIGN KEY FK_63D401098D93D649');
		$this->addSql('DROP TABLE `email_verification`');
		$this->addSql('DROP TABLE `password_recovery`');
		$this->addSql('DROP TABLE `users`');
	}

}
