<?php

/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Tests\Doctrine;

use App\Doctrine\CalculationBelowFilter;
use App\Entity\Calculation;
use App\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;

final class CalculationBelowFilterTest extends TestCase
{
    public function testCalculationEntity(): void
    {
        $manager = $this->createEntityManager();
        $filter = new CalculationBelowFilter($manager);
        $filter->setParameter(CalculationBelowFilter::MARGIN_PARAMETER, 1.1, Types::FLOAT);

        $targetEntity = self::createMock(ClassMetadata::class);
        $targetEntity->name = Calculation::class;
        $targetEntity->expects(self::exactly(2))
            ->method('getColumnName')
            ->willReturnMap([
                ['itemsTotal', 'items_total'],
                ['overallTotal', 'overall_total'],
            ]);

        $actual = $filter->addFilterConstraint($targetEntity, 'a');
        self::assertSame('a.items_total != 0 AND (a.overall_total / a.items_total) < 1.1', $actual);
    }

    public function testInvalidEntity(): void
    {
        $manager = $this->createEntityManager();
        $filter = new CalculationBelowFilter($manager);

        $targetEntity = self::createStub(ClassMetadata::class);
        $targetEntity->name = User::class;

        $actual = $filter->addFilterConstraint($targetEntity, 'a');
        self::assertSame('', $actual);
    }

    private function createEntityManager(): EntityManagerInterface
    {
        $connection = self::createStub(Connection::class);
        $connection->method('quote')
            ->willReturnArgument(0);

        $manager = self::createStub(EntityManagerInterface::class);
        $manager->method('getConnection')
            ->willReturn($connection);

        return $manager;
    }
}
