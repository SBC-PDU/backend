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
 * Initial device migration
 */
final class Version20230322224031 extends AbstractMigration {

	/**
	 * Returns the migration description
	 * @return string Description
	 */
	public function getDescription(): string {
		return 'Initial device migration';
	}

	/**
	 * Applies the migration
	 * @param Schema $schema Database schema
	 */
	public function up(Schema $schema): void {
		// this up() migration is auto-generated, please modify it to your needs
		$this->addSql('CREATE TABLE device_outputs (`index` INT NOT NULL, device VARCHAR(12) NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_86C376C192FB68E (device), PRIMARY KEY(`index`, device)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('CREATE TABLE devices (id VARCHAR(12) NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB');
		$this->addSql('ALTER TABLE device_outputs ADD CONSTRAINT FK_86C376C192FB68E FOREIGN KEY (device) REFERENCES devices (id) ON DELETE CASCADE');
		$this->addSql('ALTER TABLE users CHANGE language language VARCHAR(7) DEFAULT \'en\' NOT NULL');
	}

	/**
	 * Reverts the migration
	 * @param Schema $schema Database schema
	 */
	public function down(Schema $schema): void {
		// this down() migration is auto-generated, please modify it to your needs
		$this->addSql('ALTER TABLE device_outputs DROP FOREIGN KEY FK_86C376C192FB68E');
		$this->addSql('DROP TABLE device_outputs');
		$this->addSql('DROP TABLE devices');
		$this->addSql('ALTER TABLE users CHANGE language language VARCHAR(7) NOT NULL');
	}

}
