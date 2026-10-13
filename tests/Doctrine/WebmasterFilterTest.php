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

use App\Doctrine\WebmasterFilter;
use App\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;

final class WebmasterFilterTest extends TestCase
{
    public function testInvalidEntity(): void
    {
        $filter = $this->createFilter();
        $targetEntity = self::createStub(ClassMetadata::class);
        $targetEntity->name = self::class;

        $actual = $filter->addFilterConstraint($targetEntity, 'a');
        self::assertSame('', $actual);
    }

    public function testUserEntity(): void
    {
        $filter = $this->createFilter();
        $targetEntity = self::createStub(ClassMetadata::class);
        $targetEntity->name = User::class;

        $actual = $filter->addFilterConstraint($targetEntity, 'a');
        self::assertSame('a.role IS NULL OR a.role <> "ROLE_WEBMASTER"', $actual);
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

    private function createFilter(): WebmasterFilter
    {
        return new WebmasterFilter($this->createEntityManager());
    }
}
