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

namespace App\Tests\Entity;

use App\Entity\Task;
use App\Entity\TaskItem;
use App\Entity\TaskItemMargin;

final class TaskItemMarginTest extends EntityValidatorTestCase
{
    public function testAllLessZero(): void
    {
        $margin = $this->getTaskItemMargin(-2, -1, -1);
        $results = $this->validate($margin, 3);
        $this->validatePaths($results, 'maximum', 'minimum', 'value');
    }

    public function testMaxLessMin(): void
    {
        $margin = $this->getTaskItemMargin(10, 9, 0);
        $results = $this->validate($margin, 1);
        $this->validatePaths($results, 'maximum');
    }

    public function testMinLessZero(): void
    {
        $margin = $this->getTaskItemMargin(-1, 1, 0);
        $results = $this->validate($margin, 1);
        $this->validatePaths($results, 'minimum');
    }

    public function testParent(): void
    {
        $entity = new TaskItemMargin();
        self::assertNull($entity->taskItem);
        self::assertNull($entity->getParentEntity());

        $item = new TaskItem();
        $entity->withTaskItem($item);
        self::assertNotNull($entity->taskItem);
        self::assertSame($item, $entity->taskItem);
        self::assertNull($entity->getParentEntity());

        $task = new Task();
        $task->addItem($item);
        self::assertNotNull($entity->getParentEntity());
        self::assertSame($task, $entity->getParentEntity());
    }

    public function testValid(): void
    {
        $margin = $this->getTaskItemMargin(0, 10, 0);
        $this->validate($margin);
    }

    public function testValueLessZero(): void
    {
        $margin = $this->getTaskItemMargin(0, 1, -1);
        $results = $this->validate($margin, 1);
        $this->validatePaths($results, 'value');
    }

    public function testValues(): void
    {
        $margin = $this->getTaskItemMargin(0, 100, 10);
        self::assertSame(0.0, $margin->minimum);
        self::assertSame(100.0, $margin->maximum);
        self::assertSame(10.0, $margin->value);
        self::assertFalse($margin->contains(-1));
        self::assertTrue($margin->contains(0));
        self::assertTrue($margin->contains(99));
        self::assertFalse($margin->contains(100));
    }

    private function getTaskItemMargin(float $minimum, float $maximum, float $value): TaskItemMargin
    {
        $entity = new TaskItemMargin();
        $entity->withTaskItem(new TaskItem())
            ->withMinimum($minimum)
            ->withMaximum($maximum)
            ->withValue($value);

        return $entity;
    }
}
