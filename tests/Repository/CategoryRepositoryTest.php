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
use App\Repository\AbstractRepository;
use App\Repository\CategoryRepository;
use App\Tests\DatabaseTrait;
use App\Tests\EntityTrait\CategoryTrait;
use App\Tests\EntityTrait\ProductTrait;
use App\Tests\EntityTrait\TaskTrait;
use App\Tests\KernelServiceTestCase;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractRepository::class)]
#[CoversClass(CategoryRepository::class)]
class CategoryRepositoryTest extends KernelServiceTestCase
{
    use CategoryTrait;
    use DatabaseTrait;
    use ProductTrait;
    use TaskTrait;

    private CategoryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getService(CategoryRepository::class);
    }

    public function testCreateDefaultQueryBuilder(): void
    {
        $actual = $this->repository->createDefaultQueryBuilder();
        self::assertInstanceOf(QueryBuilder::class, $actual);
    }

    /**
     * @throws ORMException
     */
    public function testGetDropDownProducts(): void
    {
        $actual = $this->repository->getDropDownProducts();
        self::assertCount(0, $actual);

        $this->getProduct();
        $actual = $this->repository->getDropDownProducts();
        self::assertCount(0, $actual);

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

    /**
     * @throws ORMException
     */
    public function testGetDropDownTasks(): void
    {
        $actual = $this->repository->getDropDownTasks();
        self::assertCount(0, $actual);

        $this->getTask();
        $actual = $this->repository->getDropDownTasks();
        self::assertCount(0, $actual);

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

    public function testGetQueryBuilderByGroup(): void
    {
        $actual = $this->repository->getQueryBuilderByGroup();
        self::assertInstanceOf(QueryBuilder::class, $actual);
    }

    public function testGetSearchFields(): void
    {
        $actual = $this->repository->getSearchFields('group.id');
        self::assertSame('g.id', $actual);

        $actual = $this->repository->getSearchFields('groupCode');
        self::assertSame('g.code', $actual);

        $actual = $this->repository->getSearchFields('group.code');
        self::assertSame('g.code', $actual);
    }

    public function testGetSortField(): void
    {
        $actual = $this->repository->getSortField('group.id');
        self::assertSame('g.code', $actual);

        $actual = $this->repository->getSortField('groupCode');
        self::assertSame('g.code', $actual);

        $actual = $this->repository->getSortField('group.code');
        self::assertSame('g.code', $actual);
    }

    public function testGetTableQueryBuilder(): void
    {
        $actual = $this->repository->getTableQueryBuilder();
        self::assertInstanceOf(QueryBuilder::class, $actual);
    }
}
