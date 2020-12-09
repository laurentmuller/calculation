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
final class Version20201207123435 extends AbstractMigration
{
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE sy_CalculationCategory');
        $this->addSql('ALTER TABLE sy_CalculationGroup DROP FOREIGN KEY FK_CAC6A378FE54D947');
        $this->addSql('DROP INDEX IDX_CAC6A378FE54D947 ON sy_CalculationGroup');
        $this->addSql('ALTER TABLE sy_CalculationGroup CHANGE group_id category_id INT NOT NULL');
        $this->addSql('ALTER TABLE sy_CalculationGroup ADD CONSTRAINT FK_9A13452412469DE2 FOREIGN KEY (category_id) REFERENCES sy_category (id)');
        $this->addSql('CREATE INDEX IDX_CAC6A37812469DE2 ON sy_CalculationGroup (category_id)');
        $this->addSql('ALTER TABLE sy_Category DROP FOREIGN KEY FK_31E80115FE54D947');
        $this->addSql('ALTER TABLE sy_Category CHANGE group_id group_id INT DEFAULT NULL');
    }

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE sy_CalculationCategory (id INT AUTO_INCREMENT NOT NULL, category_id INT NOT NULL, group_id INT NOT NULL, amount DOUBLE PRECISION DEFAULT \'0\' NOT NULL, code VARCHAR(30) NOT NULL, INDEX IDX_3396B43B12469DE2 (category_id), INDEX IDX_3396B43BFE54D947 (group_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sy_CalculationCategory ADD CONSTRAINT FK_3396B43B12469DE2 FOREIGN KEY (category_id) REFERENCES sy_Category (id)');
        $this->addSql('ALTER TABLE sy_CalculationCategory ADD CONSTRAINT FK_3396B43BFE54D947 FOREIGN KEY (group_id) REFERENCES sy_CalculationGroup (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sy_calculationgroup DROP FOREIGN KEY FK_9A13452412469DE2');
        $this->addSql('DROP INDEX IDX_CAC6A37812469DE2 ON sy_calculationgroup');
        $this->addSql('ALTER TABLE sy_calculationgroup CHANGE category_id group_id INT NOT NULL');
        $this->addSql('ALTER TABLE sy_calculationgroup ADD CONSTRAINT FK_CAC6A378FE54D947 FOREIGN KEY (group_id) REFERENCES sy_Group (id)');
        $this->addSql('CREATE INDEX IDX_CAC6A378FE54D947 ON sy_calculationgroup (group_id)');
        $this->addSql('ALTER TABLE sy_category CHANGE group_id group_id INT NOT NULL');
        $this->addSql('ALTER TABLE sy_category ADD CONSTRAINT FK_31E80115FE54D947 FOREIGN KEY (group_id) REFERENCES sy_Group (id)');
    }
}
