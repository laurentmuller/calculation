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

namespace App\Tests\Model;

use App\Entity\Task;
use App\Entity\TaskItem;
use App\Entity\TaskItemMargin;
use App\Model\TaskComputeResult;
use App\Tests\Entity\IdTrait;
use PHPUnit\Framework\TestCase;

final class TaskComputeResultTest extends TestCase
{
    use IdTrait;

    public function testAddItem(): void
    {
        $task = $this->createTask();
        $item = $this->createTaskItem($task);
        $result = new TaskComputeResult($task, 1.0);
        self::assertSame(0.0, $result->getOverall());

        $result->addItem($item, false);
        self::assertSame(0.0, $result->getOverall());

        $result->addItem($item, true);
        self::assertSame(10.0, $result->getOverall());
    }

    public function testConstruct(): void
    {
        $task = $this->createTask();
        $result = new TaskComputeResult($task, 1.0);
        self::assertSame($task, $result->getTask());
        self::assertSame(1.0, $result->getQuantity());
    }

    public function testGetResults(): void
    {
        $task = $this->createTask();
        $result = new TaskComputeResult($task, 1.0);
        $actual = $result->getItems();
        self::assertSame([], $actual);

        $item = $this->createTaskItem($task);
        $result->addItem($item, true);

        $expected = [
            'id' => 1,
            'name' => 'item',
            'value' => 10.0,
            'amount' => 10.0,
            'checked' => true,
        ];
        $actual = $result->getItems();
        self::assertSame([$expected], $actual);
    }

    public function testJsonSerialize(): void
    {
        $task = $this->createTask();
        $result = new TaskComputeResult($task, 1.0);
        $expected = [
            'id' => 1,
            'unit' => null,
            'categoryId' => null,
            'quantity' => 1.0,
            'overall' => 0.0,
            'items' => [],
        ];
        $actual = $result->jsonSerialize();
        self::assertSame($expected, $actual);
    }

    private function createTask(): Task
    {
        $task = new Task();
        $task->setName('task');

        return self::setId($task);
    }

    private function createTaskItem(?Task $task = null): TaskItem
    {
        $item = new TaskItem();
        $item->setName('item');

        $margin = new TaskItemMargin();
        $margin->setMinimum(0)
            ->setMaximum(1000)
            ->setValue(10);
        self::setId($margin);
        $item->addMargin($margin);

        $task ??= $this->createTask();
        $task->addItem($item);

        return self::setId($item);
    }
}
