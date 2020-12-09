<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201207073354 extends AbstractMigration
{
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sy_Category DROP FOREIGN KEY FK_31E80115FE54D947');
        $this->addSql('ALTER TABLE sy_GroupMargin DROP FOREIGN KEY FK_37F96A67FE54D947');
        $this->addSql('CREATE TABLE sy_categorymargin (id INT AUTO_INCREMENT NOT NULL, category_id INT NOT NULL, minimum DOUBLE PRECISION DEFAULT \'0\' NOT NULL, maximum DOUBLE PRECISION DEFAULT \'0\' NOT NULL, margin DOUBLE PRECISION DEFAULT \'0\' NOT NULL, INDEX IDX_6CD4930712469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE sy_categorymargin ADD CONSTRAINT FK_9BA214E212469DE2 FOREIGN KEY (category_id) REFERENCES sy_category (id)');

        $this->addSql('DROP TABLE sy_Group');
        $this->addSql('DROP TABLE sy_GroupMargin');
        $this->addSql('DROP INDEX IDX_31E80115FE54D947 ON sy_Category');
        $this->addSql('ALTER TABLE sy_Category ADD parent_id INT DEFAULT NULL, DROP group_id');
        $this->addSql('ALTER TABLE sy_Category ADD CONSTRAINT FK_31E80115727ACA70 FOREIGN KEY (parent_id) REFERENCES sy_category (id)');
        $this->addSql('CREATE INDEX IDX_31E80115727ACA70 ON sy_Category (parent_id)');
    }

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sy_Group (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(30) NOT NULL, description VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_A9F6FA1C77153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sy_GroupMargin (id INT AUTO_INCREMENT NOT NULL, group_id INT NOT NULL, margin DOUBLE PRECISION DEFAULT \'0\' NOT NULL, maximum DOUBLE PRECISION DEFAULT \'0\' NOT NULL, minimum DOUBLE PRECISION DEFAULT \'0\' NOT NULL, INDEX IDX_37F96A67FE54D947 (group_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        // ALTER TABLE sy_GroupMargin ADD INDEX IDX_37F96A67FE54D947 ON (group_id)
        $this->addSql('ALTER TABLE sy_GroupMargin ADD CONSTRAINT FK_37F96A67FE54D947 FOREIGN KEY (group_id) REFERENCES sy_Group (id)');
        $this->addSql('DROP TABLE sy_categorymargin');
        //RENAME TABLE sy_CategoryMargin TO sy_GroupMargin;
        $this->addSql('ALTER TABLE sy_category DROP FOREIGN KEY FK_31E80115727ACA70');
        $this->addSql('DROP INDEX IDX_31E80115727ACA70 ON sy_category');
        $this->addSql('ALTER TABLE sy_category ADD group_id INT NOT NULL, DROP parent_id');
        $this->addSql('ALTER TABLE sy_category ADD CONSTRAINT FK_31E80115FE54D947 FOREIGN KEY (group_id) REFERENCES sy_Group (id)');
        $this->addSql('CREATE INDEX IDX_31E80115FE54D947 ON sy_category (group_id)');
    }
}
