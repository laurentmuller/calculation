<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200720145853 extends AbstractMigration
{
    public function down(): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE sy_Log (id INT AUTO_INCREMENT NOT NULL, channel VARCHAR(50) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, level VARCHAR(50) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, message LONGTEXT CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, context LONGTEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci` COMMENT \'(DC2Type:array)\', extra LONGTEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci` COMMENT \'(DC2Type:array)\', created_at DATETIME NOT NULL, user_name VARCHAR(180) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE sy_User CHANGE enabled enabled TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE sy_User RENAME INDEX uniq_da67b7b0e7927c74 TO UNIQ_2DA17977A0D96FBF');
        $this->addSql('ALTER TABLE sy_User RENAME INDEX uniq_da67b7b0f85e0677 TO UNIQ_2DA1797792FC23A8');
    }

    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf('mysql' !== $this->connection->getDatabasePlatform()->getName(), 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE sy_Log');
        $this->addSql('ALTER TABLE sy_User CHANGE enabled enabled TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE sy_User RENAME INDEX uniq_2da17977a0d96fbf TO UNIQ_DA67B7B0E7927C74');
        $this->addSql('ALTER TABLE sy_User RENAME INDEX uniq_2da1797792fc23a8 TO UNIQ_DA67B7B0F85E0677');
    }
}
