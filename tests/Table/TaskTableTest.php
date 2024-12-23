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
use PHPUnit\Framework\MockObject\Exception;
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
     * @throws Exception|\ReflectionException
     */
    public function testWithCategoryId(): void
    {
        $dataQuery = new DataQuery();
        $dataQuery->categoryId = 10;
        $this->processDataQuery($dataQuery);
    }

    /**
     * @throws Exception|\ReflectionException
     */
    public function testWithFindCategoryId(): void
    {
        $this->categoryId = 10;
        $dataQuery = new DataQuery();
        $dataQuery->categoryId = 10;
        $this->processDataQuery($dataQuery);
    }

    /**
     * @throws Exception|\ReflectionException
     */
    public function testWithFindGroupId(): void
    {
        $this->groupId = 10;
        $dataQuery = new DataQuery();
        $dataQuery->groupId = 10;
        $this->processDataQuery($dataQuery);
    }

    /**
     * @throws Exception|\ReflectionException
     */
    public function testWithGroupId(): void
    {
        $dataQuery = new DataQuery();
        $dataQuery->groupId = 10;
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

    /**
     * @throws Exception|\ReflectionException
     */
    protected function createRepository(MockObject&QueryBuilder $queryBuilder): MockObject&TaskRepository
    {
        $repository = $this->createMock(TaskRepository::class);
        $repository->method('getTableQueryBuilder')
            ->willReturn($queryBuilder);

        return $repository;
    }

    /**
     * @psalm-param TaskRepository $repository
     *
     * @throws Exception|\ReflectionException
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
