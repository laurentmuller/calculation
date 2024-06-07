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

use App\Repository\AbstractCategoryItemRepository;
use App\Repository\AbstractRepository;
use App\Repository\TaskRepository;
use App\Tests\DatabaseTrait;
use App\Tests\EntityTrait\TaskTrait;
use App\Tests\KernelServiceTestCase;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AbstractRepository::class)]
#[CoversClass(AbstractCategoryItemRepository::class)]
#[CoversClass(TaskRepository::class)]
class TaskRepositoryTest extends KernelServiceTestCase
{
    use DatabaseTrait;
    use TaskTrait;

    private TaskRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getService(TaskRepository::class);
    }

    /**
     * @throws ORMException
     */
    protected function tearDown(): void
    {
        $this->deleteTask();
        parent::tearDown();
    }

    /**
     * @throws ORMException
     */
    public function testCountCategoryReferences(): void
    {
        $category = $this->getCategory();
        $actual = $this->repository->countCategoryReferences($category);
        self::assertSame(0, $actual);
    }

    public function testGetSearchFields(): void
    {
        $actual = $this->repository->getSearchFields('categoryCode');
        self::assertSame('c.code', $actual);
        $actual = $this->repository->getSearchFields('category.code');
        self::assertSame('c.code', $actual);

        $actual = $this->repository->getSearchFields('groupCode');
        self::assertSame('g.code', $actual);
        $actual = $this->repository->getSearchFields('group.code');
        self::assertSame('g.code', $actual);
    }

    public function testGetSortedBuilder(): void
    {
        $builder = $this->repository->getSortedBuilder();
        self::assertInstanceOf(QueryBuilder::class, $builder);

        $builder = $this->repository->getSortedBuilder(false);
        self::assertInstanceOf(QueryBuilder::class, $builder);
    }

    /**
     * @throws ORMException
     */
    public function testGetSortedTask(): void
    {
        $actual = $this->repository->getSortedTask();
        self::assertCount(0, $actual);

        $this->getTask();
        $actual = $this->repository->getSortedTask();
        self::assertCount(1, $actual);

        $actual = $this->repository->getSortedTask(false);
        self::assertCount(0, $actual);
    }

    public function testGetSortFields(): void
    {
        $actual = $this->repository->getSortField('group.id');
        self::assertSame('g.code', $actual);
        $actual = $this->repository->getSortField('groupCode');
        self::assertSame('g.code', $actual);
        $actual = $this->repository->getSortField('group.code');
        self::assertSame('g.code', $actual);

        $actual = $this->repository->getSortField('category.id');
        self::assertSame('c.code', $actual);
        $actual = $this->repository->getSortField('categoryCode');
        self::assertSame('c.code', $actual);
        $actual = $this->repository->getSortField('category.code');
        self::assertSame('c.code', $actual);
    }

    public function testGetTableQueryBuilder(): void
    {
        $actual = $this->repository->getTableQueryBuilder();
        self::assertInstanceOf(QueryBuilder::class, $actual);
    }
}
