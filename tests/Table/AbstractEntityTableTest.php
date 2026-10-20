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

use App\Repository\AbstractRepository;
use App\Table\DataQuery;
use App\Tests\Fixture\FixtureEntityTable;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

final class AbstractEntityTableTest extends TestCase
{
    public function testClassNameAndRepository(): void
    {
        $repository = self::createStub(AbstractRepository::class);
        $repository->method('getClassName')
            ->willReturn(self::class);

        $table = new FixtureEntityTable($repository);
        self::assertSame(self::class, $table->getEntityClassName());
        self::assertSame($repository, $table->getRepository());
    }

    public function testHandleQuery(): void
    {
        $query = self::createStub(Query::class);
        $query->method('getResult')
            ->willReturn([]);

        $builder = self::createStub(QueryBuilder::class);
        $builder->method('getRootAliases')
            ->willReturn(['e']);
        $builder->method('getDQLPart')
            ->willReturn([]);
        $builder->method('getQuery')
            ->willReturn($query);

        $repository = self::createStub(AbstractRepository::class);
        $repository->method('createDefaultQueryBuilder')
            ->willReturn($builder);

        $query = new DataQuery();
        $query->sort = 'fake';
        $query->id = 10;

        $table = new FixtureEntityTable($repository);
        $actual = $table->handleQuery($query);
        self::assertSame(0, $actual->filtered);
    }

    public function testUpdatePartsWithInnerJoin(): void
    {
        $join = new Join(
            joinType: Join::INNER_JOIN,
            join: 'fake',
            alias: 'e'
        );
        $this->assertUpdatePart($join);
    }

    public function testUpdatePartsWithJoinPartAndOtherAlias(): void
    {
        $join = new Join(
            joinType: Join::INNER_JOIN,
            join: 'fake',
            alias: 'fake'
        );
        $this->assertUpdatePart($join, 'fake');
    }

    public function testUpdatePartsWithLeftJoin(): void
    {
        $join = new Join(
            joinType: Join::LEFT_JOIN,
            join: 'fake',
            alias: 'e'
        );
        $this->assertUpdatePart($join);
    }

    private function assertUpdatePart(Join $join, string $alias = 'e'): void
    {
        $query = self::createStub(Query::class);
        $query->method('getResult')
            ->willReturn([]);

        $builder = self::createStub(QueryBuilder::class);
        $builder->method('getRootAliases')
            ->willReturn(['e']);
        $builder->method('getQuery')
            ->willReturn($query);
        $builder->method('getDQLPart')
            ->willReturn([$alias => [$join]]);

        $repository = self::createStub(AbstractRepository::class);
        $repository->method('createDefaultQueryBuilder')
            ->willReturn($builder);

        $query = new DataQuery();
        $query->search = 'fake';

        $table = new FixtureEntityTable($repository);
        $actual = $table->handleQuery($query);
        self::assertSame(0, $actual->filtered);
    }
}
