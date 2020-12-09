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
final class Version20201207130940 extends AbstractMigration
{
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sy_CalculationItem DROP FOREIGN KEY FK_7EDAF9FA12469DE2');
        $this->addSql('DROP INDEX IDX_7EDAF9FA12469DE2 ON sy_CalculationItem');
        $this->addSql('ALTER TABLE sy_CalculationItem CHANGE category_id group_id INT NOT NULL');
        $this->addSql('ALTER TABLE sy_CalculationItem ADD CONSTRAINT FK_7EDAF9FAFE54D947 FOREIGN KEY (group_id) REFERENCES sy_calculationgroup (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_7EDAF9FAFE54D947 ON sy_CalculationItem (group_id)');
    }

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sy_calculationitem DROP FOREIGN KEY FK_7EDAF9FAFE54D947');
        $this->addSql('DROP INDEX IDX_7EDAF9FAFE54D947 ON sy_calculationitem');
        $this->addSql('ALTER TABLE sy_calculationitem CHANGE group_id category_id INT NOT NULL');
        $this->addSql('ALTER TABLE sy_calculationitem ADD CONSTRAINT FK_7EDAF9FA12469DE2 FOREIGN KEY (category_id) REFERENCES sy_CalculationCategory (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_7EDAF9FA12469DE2 ON sy_calculationitem (category_id)');
    }
}
