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

namespace App\Tests\Table;

use App\Entity\GlobalMargin;
use App\Repository\AbstractRepository;
use App\Repository\GlobalMarginRepository;
use App\Table\DataQuery;
use App\Table\GlobalMarginTable;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @extends EntityTableTestCase<GlobalMargin, GlobalMarginRepository, GlobalMarginTable>
 */
class GlobalMarginTableTest extends EntityTableTestCase
{
    public function testGetEntityClassName(): void
    {
        $expected = GlobalMargin::class;
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $repository = $this->createMockRepository($queryBuilder);
        $repository->method('getClassName')
            ->willReturn($expected);
        $table = $this->createTable($repository);
        $actual = $table->getEntityClassName();
        self::assertSame($expected, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testSearch(): void
    {
        $query = new DataQuery();
        $query->search = '10';
        $this->processDataQuery($query);
    }

    #[\Override]
    protected function createEntities(): array
    {
        $entity = new GlobalMargin();
        $entity->setMinimum(0.0)
            ->setMaximum(100.0)
            ->setMargin(1.1);

        return [$entity];
    }

    #[\Override]
    protected function createMockRepository(MockObject&QueryBuilder $queryBuilder): MockObject&GlobalMarginRepository
    {
        $repository = $this->createMock(GlobalMarginRepository::class);
        $repository->method('createDefaultQueryBuilder')
            ->willReturn($queryBuilder);

        return $repository;
    }

    /**
     * @phpstan-param GlobalMarginRepository $repository
     */
    #[\Override]
    protected function createTable(AbstractRepository $repository): GlobalMarginTable
    {
        $service = $this->createMockIndexService();

        return new GlobalMarginTable($repository, $service);
    }
}
