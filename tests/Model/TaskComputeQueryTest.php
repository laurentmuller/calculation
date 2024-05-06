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
use App\Model\TaskComputeQuery;
use App\Tests\Entity\IdTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TaskComputeQuery::class)]
class TaskComputeQueryTest extends TestCase
{
    use IdTrait;

    public function testConstruct(): void
    {
        $query = new TaskComputeQuery(0);
        self::assertSame(0, $query->getId());
        self::assertSame(1.0, $query->getQuantity());
        self::assertSame([], $query->getItems());

        $query = new TaskComputeQuery(1, 2.0, [1, 2, 3]);
        self::assertSame(1, $query->getId());
        self::assertSame(2.0, $query->getQuantity());
        self::assertSame([1, 2, 3], $query->getItems());
    }

    /**
     * @throws \ReflectionException
     */
    public function testInstance(): void
    {
        $task = $this->createTask();
        $query = TaskComputeQuery::instance($task);
        self::assertSame(1, $query->getId());
        self::assertSame(1.0, $query->getQuantity());
        self::assertSame([], $query->getItems());
    }

    /**
     * @throws \ReflectionException
     */
    private function createTask(): Task
    {
        $task = new Task();
        $task->setName('name');

        return self::setId($task);
    }
}
