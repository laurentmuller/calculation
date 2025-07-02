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
use App\Repository\CalculationRepository;
use App\Table\CalculationDuplicateTable;
use App\Table\DataQuery;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CalculationDuplicateTableTest extends TestCase
{
    public function testDefault(): void
    {
        $table = $this->createTable();
        self::assertCount(0, $table);
        self::assertSame(Calculation::class, $table->getEntityClassName());
        self::assertInstanceOf(CalculationRepository::class, $table->getRepository());
        self::assertSame('duplicate.empty', $table->getEmptyMessage());
    }

    public function testWithData(): void
    {
        $entity = $this->getEntity();
        $table = $this->createTable([$entity]);

        $query = new DataQuery();
        $query->limit = 15;
        $results = $table->processDataQuery($query);
        self::assertCount(1, $results->rows);
    }

    public function testWithoutData(): void
    {
        $table = $this->createTable();
        $query = new DataQuery();
        $query->limit = 15;
        $results = $table->processDataQuery($query);
        self::assertEmpty($results->rows);
    }

    private function createMockRepository(array $entities = []): MockObject&CalculationRepository
    {
        $repository = $this->createMock(CalculationRepository::class);
        $repository->method('getClassName')
            ->willReturn(Calculation::class);
        $repository->method('getItemsDuplicate')
            ->willReturn($entities);

        return $repository;
    }

    private function createTable(array $entities = []): CalculationDuplicateTable
    {
        $repository = $this->createMockRepository($entities);

        return new CalculationDuplicateTable($repository);
    }

    private function getEntity(): array
    {
        return [
            'id' => 1,
            'date' => new \DateTime('2024-02-02'),
            'stateCode' => 'stateCode',
            'customer' => 'customer',
            'description' => 'description',
            'items' => [
                [
                    'description' => 'description',
                    'quantity' => 1.0,
                    'price' => 1.0,
                    'count' => 2,
                ],
            ],
        ];
    }
}
