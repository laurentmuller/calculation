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

namespace App\Tests\Report;

use App\Controller\AbstractController;
use App\Entity\Category;
use App\Entity\Group;
use App\Entity\Task;
use App\Entity\TaskItem;
use App\Entity\TaskItemMargin;
use App\Report\TasksReport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(TasksReport::class)]
class TasksReportTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testRender(): void
    {
        $controller = $this->createMock(AbstractController::class);

        $group = new Group();
        $group->setCode('Group');

        $category = new Category();
        $category->setCode('Category')
            ->setGroup($group);

        $taskItemMargin1 = new TaskItemMargin();
        $taskItemMargin1->setMinimum(0.0)
            ->setMaximum(100.0)
            ->setValue(10.0);

        $taskItemMargin2 = new TaskItemMargin();
        $taskItemMargin2->setMinimum(0.0)
            ->setMaximum(100.0)
            ->setValue(10.0);

        $taskItem1 = new TaskItem();
        $taskItem1->setName('TaskItem1');
        $taskItem1->addMargin($taskItemMargin1)
            ->addMargin($taskItemMargin2);

        $task1 = new Task();
        $task1->setName('Task1')
            ->setCategory($category);
        $task1->addItem($taskItem1);

        $taskItem2 = new TaskItem();
        $taskItem2->setName('TaskItem2');

        $task2 = new Task();
        $task2->setName('Task2')
            ->setCategory($category)
            ->addItem($taskItem2);

        $task3 = new Task();
        $task3->setName('Task3');

        $report = new TasksReport($controller, [$task1, $task2, $task3]);
        $actual = $report->render();
        self::assertTrue($actual);
    }
}
