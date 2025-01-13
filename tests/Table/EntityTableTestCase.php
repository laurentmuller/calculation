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

use App\Interfaces\EntityInterface;
use App\Repository\AbstractRepository;
use App\Table\AbstractEntityTable;
use App\Table\DataQuery;
use App\Tests\Entity\IdTrait;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @template TEntity of EntityInterface
 * @template TRepository of AbstractRepository<TEntity>
 * @template TEntityTable of AbstractEntityTable<TEntity, TRepository>
 */
abstract class EntityTableTestCase extends TestCase
{
    use IdTrait;

    /**
     * @throws Exception|\ReflectionException
     */
    public function testWithDefaultDataQuery(): void
    {
        $this->processDataQuery(new DataQuery());
    }

    /**
     * @throws Exception|\ReflectionException
     */
    public function testWithSelection(): void
    {
        $query = new DataQuery();
        $query->id = 1;

        $this->processDataQuery($query);
    }

    /**
     * @psalm-return TEntity[]|array[]
     */
    abstract protected function createEntities(): array;

    /**
     * @phpstan-return MockObject&Query<array-key, mixed>
     *
     * @throws Exception
     */
    protected function createMockQuery(array $entities): MockObject&Query
    {
        $query = $this->createMock(Query::class);
        $query->method('getResult')
            ->willReturn($entities);

        return $query;
    }

    /**
     * @phpstan-param MockObject&Query<array-key, mixed> $query
     *
     * @throws Exception
     */
    protected function createMockQueryBuilder(MockObject&Query $query): MockObject&QueryBuilder
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('getQuery')
            ->willReturn($query);
        $queryBuilder->method('getRootAliases')
            ->willReturn([AbstractRepository::DEFAULT_ALIAS]);
        $queryBuilder->method('expr')
            ->willReturn(new Expr());

        return $queryBuilder;
    }

    /**
     * @psalm-return MockObject&TRepository
     *
     * @throws Exception
     */
    abstract protected function createMockRepository(MockObject&QueryBuilder $queryBuilder): MockObject&AbstractRepository;

    /**
     * @psalm-param MockObject&TRepository $repository
     *
     * @psalm-return TEntityTable
     *
     * @throws Exception
     *
     * @phpstan-ignore-next-line
     */
    abstract protected function createTable(MockObject&AbstractRepository $repository): AbstractEntityTable;

    /**
     * @throws Exception|\ReflectionException
     */
    protected function processDataQuery(DataQuery $dataQuery): void
    {
        $entities = $this->updateIds($this->createEntities());
        $query = $this->createMockQuery($entities);
        $queryBuilder = $this->createMockQueryBuilder($query);
        $repository = $this->createMockRepository($queryBuilder);
        $table = $this->createTable($repository);

        $results = $table->processDataQuery($dataQuery);
        self::assertSame(Response::HTTP_OK, $results->status);
        self::assertCount(\count($entities), $results->rows);
    }

    /**
     * @psalm-param TEntity[]|array[] $entities
     *
     * @psalm-return TEntity[]|array[]
     *
     * @throws \ReflectionException
     */
    protected function updateIds(array $entities): array
    {
        $index = 1;
        foreach ($entities as $entity) {
            if ($entity instanceof EntityInterface) {
                self::setId($entity, $index++);
            }
        }

        return $entities;
    }

    /**
     * @psalm-param array<string, string|int> $parameters
     */
    protected function updateQueryParameters(DataQuery $dataQuery, array $parameters): void
    {
        foreach ($parameters as $key => $value) {
            $dataQuery->addParameter($key, $value);
        }
    }
}
