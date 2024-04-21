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

namespace App\Tests\Service;

use App\Entity\Category;
use App\Entity\Task;
use App\Entity\TaskItem;
use App\Model\TaskComputeQuery;
use App\Model\TaskComputeResult;
use App\Repository\TaskRepository;
use App\Service\TaskService;
use App\Tests\Entity\IdTrait;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(TaskService::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(TaskComputeResult::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(TaskComputeQuery::class)]
class TaskServiceTest extends TestCase
{
    use IdTrait;

    /**
     * @throws Exception
     * @throws \ReflectionException
     */
    public function testComputeQueryEmpty(): void
    {
        $quantity = 2.0;
        $task = $this->createTask();
        $repository = $this->createRepository($task);
        $service = new TaskService($repository);
        $query = TaskComputeQuery::instance($task, $quantity);

        $actual = $service->computeQuery($query);
        self::assertNotNull($actual);
        self::assertEmpty($actual->getResults());
        self::assertSame($quantity, $actual->getQuantity());
        self::assertSame(0.0, $actual->getOverall());
        self::assertSame($task->getId(), $actual->getTask()->getId());
    }

    /**
     * @throws Exception
     */
    public function testComputeQueryNoTask(): void
    {
        $repository = $this->createRepository(null);
        $service = new TaskService($repository);
        $query = new TaskComputeQuery(1, 1.0, []);
        self::assertSame(1, $query->getId());
        self::assertSame(1.0, $query->getQuantity());
        self::assertEmpty($query->getItems());

        $actual = $service->computeQuery($query);
        self::assertNull($actual);
    }

    /**
     * @throws Exception
     * @throws \ReflectionException
     */
    public function testComputeQueryOneItem(): void
    {
        $quantity = 2.0;
        $task = $this->createTask();
        $this->createTaskItem($task);
        $repository = $this->createRepository($task);
        $service = new TaskService($repository);
        $query = TaskComputeQuery::instance($task, $quantity);

        $actual = $service->computeQuery($query);
        self::assertNotNull($actual);
        self::assertCount(1, $actual->getResults());
        self::assertSame($quantity, $actual->getQuantity());
        self::assertSame($task->getId(), $actual->getTask()->getId());
    }

    /**
     * @throws Exception
     * @throws \ReflectionException
     */
    public function testGetSortedTasks(): void
    {
        $task = $this->createTask();
        $repository = $this->createRepository($task);
        $service = new TaskService($repository);
        $tasks = $service->getSortedTasks();
        self::assertCount(1, $tasks);
        $actual = $tasks[0];
        self::assertSame($task->getName(), $actual->getName());
    }

    /**
     * @throws \ReflectionException
     */
    public function testResultSerialize(): void
    {
        $task = $this->createTask();
        $result = new TaskComputeResult($task, 1.0);

        $expected = [
            'id' => $task->getId(),
            'unit' => $task->getUnit(),
            'categoryId' => $task->getCategoryId(),
            'quantity' => 1.0,
            'overall' => 0.0,
            'results' => [],
        ];
        $actual = $result->jsonSerialize();
        self::assertSame($expected, $actual);
    }

    /**
     * @throws Exception
     */
    private function createRepository(?Task $task): TaskRepository
    {
        $tasks = $task instanceof Task ? [$task] : [];
        $repository = $this->createMock(TaskRepository::class);
        $repository->expects(self::any())
            ->method('getSortedTask')
            ->willReturn($tasks);

        $repository->expects(self::any())
            ->method('find')
            ->willReturn($task);

        return $repository;
    }

    /**
     * @throws \ReflectionException
     */
    private function createTask(): Task
    {
        $task = new Task();
        $task->setName('task');
        $category = new Category();
        $category->setCode('code');
        $category->addTask($task);
        $this->setId($category);

        return $this->setId($task);
    }

    private function createTaskItem(Task $task): TaskItem
    {
        $item = new TaskItem();
        $item->setName('taskitem');
        $task->addItem($item);

        return $this->setId($item);
    }
}
