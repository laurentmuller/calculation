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
use App\Tests\Fixture\FakeEntityTable;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class AbstractEntityTableTest extends TestCase
{
    public function testHandleQuery(): void
    {
        $query = $this->createMock(Query::class);
        $query->method('getResult')
            ->willReturn([]);

        $builder = $this->createMock(QueryBuilder::class);
        $builder->method('getRootAliases')
            ->willReturn(['e']);
        $builder->method('getDQLPart')
            ->willReturn([]);
        $builder->method('getQuery')
            ->willReturn($query);

        $repository = $this->createMock(AbstractRepository::class);
        $repository->method('createDefaultQueryBuilder')
            ->willReturn($builder);

        $table = new FakeEntityTable($repository);
        $table->handleQuery(new DataQuery());
        self::expectNotToPerformAssertions();
    }
}
