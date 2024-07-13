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

class TaskItemMarginTest extends EntityValidatorTestCase
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
        self::assertNull($entity->getTaskItem());
        self::assertNull($entity->getParentEntity());

        $item = new TaskItem();
        $entity->setTaskItem($item);
        self::assertSame($item, $entity->getTaskItem());
        self::assertNull($entity->getParentEntity());

        $task = new Task();
        $task->addItem($item);
        self::assertSame($item, $entity->getTaskItem());
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
        self::assertSame(0.0, $margin->getMinimum());
        self::assertSame(100.0, $margin->getMaximum());
        self::assertSame(10.0, $margin->getValue());
        self::assertFalse($margin->contains(-1));
        self::assertTrue($margin->contains(0));
        self::assertTrue($margin->contains(99));
        self::assertFalse($margin->contains(100));
    }

    private function getTaskItemMargin(float $minimum, float $maximum, float $value): TaskItemMargin
    {
        $entity = new TaskItemMargin();
        $entity->setTaskItem(new TaskItem())
            ->setMinimum($minimum)
            ->setMaximum($maximum)
            ->setValue($value);

        return $entity;
    }
}
