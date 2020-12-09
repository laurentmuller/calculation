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
final class Version20201209094910 extends AbstractMigration
{
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        //$this->addSql('ALTER TABLE sy_CalculationLine DROP FOREIGN KEY FK_B0D56812727ACA70');
        //$this->addSql('DROP TABLE sy_CalculationLine');

        $this->addSql('ALTER TABLE sy_DigiPrintItem DROP FOREIGN KEY FK_A5BE5C072E306EB0');
        $this->addSql('DROP TABLE sy_DigiPrint');
        $this->addSql('DROP TABLE sy_DigiPrintItem');
    }

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        //$this->addSql('CREATE TABLE sy_CalculationLine (id INT AUTO_INCREMENT NOT NULL, calculation_id INT NOT NULL, parent_id INT DEFAULT NULL, amount DOUBLE PRECISION DEFAULT \'0\' NOT NULL, description VARCHAR(255) NOT NULL, margin DOUBLE PRECISION DEFAULT \'1\' NOT NULL, quantity DOUBLE PRECISION DEFAULT \'1\' NOT NULL, type SMALLINT NOT NULL, INDEX IDX_B0D56812CE3D4B33 (calculation_id), INDEX IDX_B0D56812727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        //$this->addSql('ALTER TABLE sy_CalculationLine ADD CONSTRAINT FK_B0D56812CE3D4B33 FOREIGN KEY (calculation_id) REFERENCES sy_Calculation (id) ON DELETE CASCADE');
        //$this->addSql('ALTER TABLE sy_CalculationLine ADD CONSTRAINT FK_B0D56812727ACA70 FOREIGN KEY (parent_id) REFERENCES sy_CalculationLine (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE sy_DigiPrint (id INT AUTO_INCREMENT NOT NULL, format VARCHAR(30) NOT NULL, height SMALLINT NOT NULL, width SMALLINT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sy_DigiPrintItem (id INT AUTO_INCREMENT NOT NULL, digi_print_id INT NOT NULL, amount DOUBLE PRECISION NOT NULL, maximum SMALLINT NOT NULL, minimum SMALLINT NOT NULL, type SMALLINT NOT NULL, INDEX IDX_A5BE5C072E306EB0 (digi_print_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sy_DigiPrintItem ADD CONSTRAINT FK_A5BE5C072E306EB0 FOREIGN KEY (digi_print_id) REFERENCES sy_DigiPrint (id) ON DELETE CASCADE');
    }
}
