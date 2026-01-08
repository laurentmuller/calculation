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

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Task;
use App\Repository\CategoryRepository;
use App\Tests\EntityTrait\CategoryTrait;
use App\Tests\EntityTrait\ProductTrait;
use App\Tests\EntityTrait\TaskTrait;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends AbstractRepositoryTestCase<Category, CategoryRepository>
 */
final class CategoryRepositoryTest extends AbstractRepositoryTestCase
{
    use CategoryTrait;
    use ProductTrait;
    use TaskTrait;

    public function testCreateDefaultQueryBuilder(): void
    {
        $this->repository->createDefaultQueryBuilder();
        self::expectNotToPerformAssertions();
    }

    public function testGetDropDownProducts(): void
    {
        $actual = $this->repository->getDropDownProducts();
        self::assertEmpty($actual);

        $this->getProduct();
        $actual = $this->repository->getDropDownProducts();
        self::assertEmpty($actual);

        $category = new Category();
        $category->setCode('My Code')
            ->setGroup($this->getGroup());
        $this->addEntity($category);

        $product = new Product();
        $product->setDescription('My Description')
            ->setCategory($category);
        $this->addEntity($product);

        try {
            $actual = $this->repository->getDropDownProducts();
            self::assertCount(1, $actual);
        } finally {
            $this->deleteEntity($product);
            $this->deleteEntity($category);
        }
    }

    public function testGetDropDownTasks(): void
    {
        $actual = $this->repository->getDropDownTasks();
        self::assertEmpty($actual);

        $this->getTask();
        $actual = $this->repository->getDropDownTasks();
        self::assertEmpty($actual);

        $category = new Category();
        $category->setCode('My Code')
            ->setGroup($this->getGroup());
        $this->addEntity($category);

        $task = new Task();
        $task->setName('My Name')
            ->setCategory($category);
        $this->addEntity($task);

        try {
            $actual = $this->repository->getDropDownTasks();
            self::assertCount(1, $actual);
        } finally {
            $this->deleteEntity($task);
            $this->deleteEntity($category);
        }
    }

    public function testGetQueryBuilderByGroupFilterNone(): void
    {
        $actual = $this->repository->getQueryBuilderByGroup();
        self::assertInstanceOf(QueryBuilder::class, $actual);
    }

    public function testGetQueryBuilderByGroupFilterProduct(): void
    {
        $actual = $this->repository->getQueryBuilderByGroup(CategoryRepository::FILTER_PRODUCTS);
        self::assertInstanceOf(QueryBuilder::class, $actual);
    }

    public function testGetQueryBuilderByGroupFilterTask(): void
    {
        $actual = $this->repository->getQueryBuilderByGroup(CategoryRepository::FILTER_TASKS);
        self::assertInstanceOf(QueryBuilder::class, $actual);
    }

    public function testGetSearchFields(): void
    {
        $this->assertSameSearchField('group.id', 'g.id');
        $this->assertSameSearchField('groupCode', 'g.code');
        $this->assertSameSearchField('group.code', 'g.code');
    }

    public function testGetSortField(): void
    {
        $this->assertSameSortField('group.id', 'g.code');
        $this->assertSameSortField('groupCode', 'g.code');
        $this->assertSameSortField('group.code', 'g.code');
    }

    public function testGetTableQueryBuilder(): void
    {
        $actual = $this->repository->getTableQueryBuilder();
        self::assertInstanceOf(QueryBuilder::class, $actual);
    }

    #[\Override]
    protected function getRepositoryClass(): string
    {
        return CategoryRepository::class;
    }
}
