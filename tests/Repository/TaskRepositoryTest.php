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

namespace App\Tests\Repository;

use App\Entity\Task;
use App\Repository\TaskRepository;
use App\Tests\EntityTrait\TaskTrait;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends AbstractRepositoryTestCase<Task, TaskRepository>
 */
final class TaskRepositoryTest extends AbstractRepositoryTestCase
{
    use TaskTrait;

    #[\Override]
    protected function tearDown(): void
    {
        $this->deleteTask();
        parent::tearDown();
    }

    public function testCountCategoryReferences(): void
    {
        $category = $this->getCategory();
        $actual = $this->repository->countCategoryReferences($category);
        self::assertSame(0, $actual);
    }

    public function testGetSearchFields(): void
    {
        $this->assertSameSearchField('group.id', 'g.id');
        $this->assertSameSearchField('groupCode', 'g.code');
        $this->assertSameSearchField('group.code', 'g.code');

        $this->assertSameSearchField('category.id', 'c.id');
        $this->assertSameSearchField('categoryCode', 'c.code');
        $this->assertSameSearchField('category.code', 'c.code');
    }

    public function testGetSortedBuilder(): void
    {
        $builder = $this->repository->getSortedBuilder();
        self::assertInstanceOf(QueryBuilder::class, $builder);

        $builder = $this->repository->getSortedBuilder(false);
        self::assertInstanceOf(QueryBuilder::class, $builder);
    }

    public function testGetSortedTask(): void
    {
        $actual = $this->repository->getSortedTask();
        self::assertEmpty($actual);

        $this->getTask();
        $actual = $this->repository->getSortedTask();
        self::assertCount(1, $actual);

        $actual = $this->repository->getSortedTask(false);
        self::assertEmpty($actual);
    }

    public function testGetSortFields(): void
    {
        $this->assertSameSortField('group.id', 'g.code');
        $this->assertSameSortField('groupCode', 'g.code');
        $this->assertSameSortField('group.code', 'g.code');

        $this->assertSameSortField('category.id', 'c.code');
        $this->assertSameSortField('categoryCode', 'c.code');
        $this->assertSameSortField('category.code', 'c.code');
    }

    public function testGetTableQueryBuilder(): void
    {
        $actual = $this->repository->getTableQueryBuilder();
        self::assertInstanceOf(QueryBuilder::class, $actual);
    }

    #[\Override]
    protected function getRepositoryClass(): string
    {
        return TaskRepository::class;
    }
}
