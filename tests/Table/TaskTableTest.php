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

use App\Entity\Category;
use App\Entity\Group;
use App\Entity\Task;
use App\Repository\AbstractRepository;
use App\Repository\CategoryRepository;
use App\Repository\GroupRepository;
use App\Repository\TaskRepository;
use App\Table\DataQuery;
use App\Table\TaskTable;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @extends EntityTableTestCase<Task, TaskRepository, TaskTable>
 */
class TaskTableTest extends EntityTableTestCase
{
    private int $categoryId;
    private int $groupId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->categoryId = 0;
        $this->groupId = 0;
    }

    /**
     * @throws \ReflectionException
     */
    public function testWithCategoryId(): void
    {
        $parameters = ['categoryId' => 10];
        $dataQuery = new DataQuery();
        $this->updateQueryParameters($dataQuery, $parameters);
        $this->processDataQuery($dataQuery);
    }

    /**
     * @throws \ReflectionException
     */
    public function testWithFindCategoryId(): void
    {
        $this->categoryId = 10;
        $parameters = ['categoryId' => 10];
        $dataQuery = new DataQuery();
        $this->updateQueryParameters($dataQuery, $parameters);
        $this->processDataQuery($dataQuery);
    }

    /**
     * @throws \ReflectionException
     */
    public function testWithFindGroupId(): void
    {
        $this->groupId = 10;
        $parameters = ['groupId' => 10];
        $dataQuery = new DataQuery();
        $this->updateQueryParameters($dataQuery, $parameters);
        $this->processDataQuery($dataQuery);
    }

    /**
     * @throws \ReflectionException
     */
    public function testWithGroupId(): void
    {
        $parameters = ['groupId' => 10];
        $dataQuery = new DataQuery();
        $this->updateQueryParameters($dataQuery, $parameters);
        $this->processDataQuery($dataQuery);
    }

    protected function createEntities(): array
    {
        $entity = [
            'id' => 1,
            'name' => 'name',
            'price' => 12.5,
            'unit' => 'unit',
            'supplier' => 'supplier',
            'groupCode' => 'groupCode',
            'categoryCode' => 'categoryCode',
            'items' => 10,
        ];

        return [$entity];
    }

    protected function createMockRepository(MockObject&QueryBuilder $queryBuilder): MockObject&TaskRepository
    {
        $repository = $this->createMock(TaskRepository::class);
        $repository->method('getTableQueryBuilder')
            ->willReturn($queryBuilder);

        return $repository;
    }

    /**
     * @psalm-param TaskRepository $repository
     *
     * @throws \ReflectionException
     */
    protected function createTable(AbstractRepository $repository): TaskTable
    {
        $categoryRepository = $this->createMock(CategoryRepository::class);
        if (0 !== $this->categoryId) {
            $category = new Category();
            $category->setCode('code');
            self::setId($category);
            $categoryRepository->method('find')
                ->willReturn($category);
        }

        $groupRepository = $this->createMock(GroupRepository::class);
        if (0 !== $this->groupId) {
            $category = new Group();
            $category->setCode('code');
            self::setId($category);
            $groupRepository->method('find')
                ->willReturn($category);
        }

        return new TaskTable($repository, $categoryRepository, $groupRepository);
    }
}
