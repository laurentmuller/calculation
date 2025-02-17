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
use App\Entity\CalculationState;
use App\Repository\AbstractRepository;
use App\Repository\CalculationRepository;
use App\Repository\CalculationStateRepository;
use App\Table\CalculationTable;
use App\Table\DataQuery;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use Twig\Environment;

/**
 * @extends EntityTableTestCase<Calculation, CalculationRepository, CalculationTable>
 */
class CalculationTableTest extends EntityTableTestCase
{
    private int $id;
    private int $stateId;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->id = 0;
        $this->stateId = 0;
    }

    /**
     * @throws \ReflectionException
     */
    public function testWithCallback(): void
    {
        $parameters = ['stateId' => 10];
        $dataQuery = new DataQuery();
        $dataQuery->callback = true;
        $this->updateQueryParameters($dataQuery, $parameters);
        $this->processDataQuery($dataQuery);
    }

    /**
     * @throws \ReflectionException
     */
    public function testWithFindStateId(): void
    {
        $this->stateId = 10;
        $parameters = ['stateId' => 10];
        $dataQuery = new DataQuery();
        $this->updateQueryParameters($dataQuery, $parameters);
        $this->processDataQuery($dataQuery);
    }

    /**
     * @throws \ReflectionException
     */
    public function testWithSelectionFound(): void
    {
        $this->id = 3;
        $dataQuery = new DataQuery();
        $dataQuery->id = 3;
        $dataQuery->limit = 1;
        $this->processDataQuery($dataQuery);
    }

    /**
     * @throws \ReflectionException
     */
    public function testWithSelectionNotFound(): void
    {
        $dataQuery = new DataQuery();
        $dataQuery->id = 3;
        $this->processDataQuery($dataQuery);
    }

    /**
     * @throws \ReflectionException
     */
    public function testWithStateEditable(): void
    {
        $parameters = ['stateEditable' => 1];
        $dataQuery = new DataQuery();
        $this->updateQueryParameters($dataQuery, $parameters);
        $this->processDataQuery($dataQuery);
    }

    /**
     * @throws \ReflectionException
     */
    public function testWithStateId(): void
    {
        $parameters = ['stateId' => 10];
        $dataQuery = new DataQuery();
        $this->updateQueryParameters($dataQuery, $parameters);
        $this->processDataQuery($dataQuery);
    }

    /**
     * @psalm-return array[]
     */
    #[\Override]
    protected function createEntities(): array
    {
        $entityEditable = [
            'id' => 1,
            'date' => new \DateTime('2024-11-10'),
            'customer' => 'customer 1',
            'description' => 'description 1',
            'overallTotal' => 1000.0,
            'overallMargin' => 1.0,
            'stateCode' => 'editableCode',
            'stateColor' => 'editableColor',
            'editable' => true,
        ];
        $entityNotEditable = [
            'id' => 2,
            'date' => new \DateTime('2024-11-10'),
            'customer' => 'customer 2',
            'description' => 'description 2',
            'overallTotal' => 1000.0,
            'overallMargin' => 1.0,
            'stateCode' => 'notEditableCode',
            'stateColor' => 'notEditableColor',
            'editable' => false,
        ];

        return [$entityEditable, $entityNotEditable];
    }

    #[\Override]
    protected function createMockQuery(array $entities): MockObject&Query
    {
        $query = parent::createMockQuery($entities);
        $query->method('getOneOrNullResult')
            ->willReturnCallback(fn (): ?array => $this->getOneOrNullResult());

        return $query;
    }

    #[\Override]
    protected function createMockRepository(MockObject&QueryBuilder $queryBuilder): MockObject&CalculationRepository
    {
        $repository = $this->createMock(CalculationRepository::class);
        $repository->method('getTableQueryBuilder')
            ->willReturn($queryBuilder);

        return $repository;
    }

    /**
     * @psalm-param  CalculationRepository $repository
     *
     * @throws \ReflectionException
     */
    #[\Override]
    protected function createTable(AbstractRepository $repository): CalculationTable
    {
        $stateRepository = $this->createMockCalculationStateRepository();
        $twig = $this->createMock(Environment::class);

        return new CalculationTable($repository, $stateRepository, $twig);
    }

    /**
     * @throws \ReflectionException
     */
    private function createMockCalculationStateRepository(): MockObject&CalculationStateRepository
    {
        $repository = $this->createMock(CalculationStateRepository::class);
        if (0 === $this->stateId) {
            return $repository;
        }

        $state = new CalculationState();
        $state->setCode('code');
        self::setId($state);
        $repository->method('find')
            ->willReturn($state);

        return $repository;
    }

    private function getOneOrNullResult(): ?array
    {
        return 0 === $this->id ? null : $this->createEntities()[0];
    }
}
