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
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Twig\Environment;

/**
 * @extends EntityTableTestCase<Calculation, CalculationRepository, CalculationTable>
 */
class CalculationTableTest extends EntityTableTestCase
{
    private int $stateId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stateId = 0;
    }

    /**
     * @throws Exception|\ReflectionException
     */
    public function testWithFindStateId(): void
    {
        $this->stateId = 10;
        $parameters = ['stateId' => 10];
        $dataQuery = new DataQuery();
        foreach ($parameters as $key => $value) {
            $dataQuery->addParameter($key, $value);
        }
        $this->processDataQuery($dataQuery);
    }

    /**
     * @throws Exception|\ReflectionException
     */
    public function testWithStateEditable(): void
    {
        $parameters = ['stateEditable' => 1];
        $dataQuery = new DataQuery();
        foreach ($parameters as $key => $value) {
            $dataQuery->addParameter($key, $value);
        }
        $this->processDataQuery($dataQuery);
    }

    /**
     * @throws Exception|\ReflectionException
     */
    public function testWithStateId(): void
    {
        $parameters = ['stateId' => 10];
        $dataQuery = new DataQuery();
        foreach ($parameters as $key => $value) {
            $dataQuery->addParameter($key, $value);
        }
        $this->processDataQuery($dataQuery);
    }

    protected function createEntities(): array
    {
        $entityEditable = [
            'id' => 1,
            'date' => new \DateTime('2024-11-10'),
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
            'date' => new \DateTime('2024-11-10'),
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

    protected function createRepository(MockObject&QueryBuilder $queryBuilder): MockObject&CalculationRepository
    {
        $repository = $this->createMock(CalculationRepository::class);
        $repository->method('getTableQueryBuilder')
            ->willReturn($queryBuilder);

        return $repository;
    }

    /**
     * @psalm-param  CalculationRepository $repository
     *
     * @throws Exception|\ReflectionException
     */
    protected function createTable(AbstractRepository $repository): CalculationTable
    {
        $stateRepository = $this->createMock(CalculationStateRepository::class);
        if (0 !== $this->stateId) {
            $state = new CalculationState();
            $state->setCode('code');
            self::setId($state);
            $stateRepository->method('find')
                ->willReturn($state);
        }
        $twig = $this->createMock(Environment::class);

        return new CalculationTable($repository, $stateRepository, $twig);
    }
}
