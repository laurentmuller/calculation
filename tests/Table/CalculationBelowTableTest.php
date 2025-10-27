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

use App\Entity\Calculation;
use App\Repository\AbstractRepository;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Service\ApplicationService;
use App\Table\CalculationBelowTable;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Clock\DatePoint;
use Twig\Environment;

/**
 * @extends EntityTableTestCase<Calculation, CalculationRepository, CalculationBelowTable>
 */
final class CalculationBelowTableTest extends EntityTableTestCase
{
    private int $countItemsBelow;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->countItemsBelow = 10;
    }

    /**
     * @throws \ReflectionException
     */
    public function testWithCountItemsBelow(): void
    {
        $this->processCountItemsBelow(10, null);
        $this->processCountItemsBelow(0, 'below.empty');
    }

    #[\Override]
    protected function createEntities(): array
    {
        $entityEditable = [
            'id' => 1,
            'date' => new DatePoint('2024-11-10'),
            'customer' => 'customer',
            'description' => 'description',
            'overallTotal' => 1000.0,
            'overallMargin' => 1.0,
            'stateCode' => 'stateCode',
            'stateColor' => 'stateColor',
            'editable' => true,
        ];
        $entityNotEditable = [
            'id' => 2,
            'date' => new DatePoint('2024-11-10'),
            'customer' => 'customer',
            'description' => 'description',
            'overallTotal' => 1000.0,
            'overallMargin' => 1.0,
            'stateCode' => 'stateCode',
            'stateColor' => 'stateColor',
            'editable' => false,
        ];

        return [$entityEditable, $entityNotEditable];
    }

    #[\Override]
    protected function createMockQueryBuilder(MockObject&Query $query): MockObject&QueryBuilder
    {
        $queryBuilder = parent::createMockQueryBuilder($query);
        $queryBuilder->method('andWhere')
            ->willReturn($queryBuilder);
        $queryBuilder->method('setParameter')
            ->willReturn($queryBuilder);

        return $queryBuilder;
    }

    #[\Override]
    protected function createMockRepository(MockObject&QueryBuilder $queryBuilder): MockObject&CalculationRepository
    {
        $repository = $this->createMock(CalculationRepository::class);
        $repository->method('getTableQueryBuilder')
            ->willReturn($queryBuilder);

        $repository->method('countItemsBelow')
            ->willReturn($this->countItemsBelow);

        return $repository;
    }

    /**
     * @phpstan-param CalculationRepository $repository
     */
    #[\Override]
    protected function createTable(AbstractRepository $repository): CalculationBelowTable
    {
        $stateRepository = $this->createMock(CalculationStateRepository::class);
        $twig = $this->createMock(Environment::class);
        $service = $this->createMock(ApplicationService::class);
        $service->method('getMinMargin')
            ->willReturn(1.1);

        return new CalculationBelowTable($repository, $stateRepository, $twig, $service);
    }

    /**
     * @throws \ReflectionException
     */
    private function processCountItemsBelow(int $count, mixed $expected): void
    {
        $this->countItemsBelow = $count;
        $entities = $this->updateIds($this->createEntities());
        $query = $this->createMockQuery($entities);
        $queryBuilder = $this->createMockQueryBuilder($query);
        $repository = $this->createMockRepository($queryBuilder);
        $table = $this->createTable($repository);
        $actual = $table->getEmptyMessage();
        self::assertSame($expected, $actual);
    }
}
