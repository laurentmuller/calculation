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

use App\Entity\TaskItem;
use App\Entity\TaskItemMargin;

/**
 * Unit test for {@link TaskItemMargin} class.
 */
class TaskItemMarginTest extends AbstractEntityValidatorTest
{
    public function testAllLessZero(): void
    {
        $margin = $this->getTaskItemMargin(-2, -1, -1);
        $this->validate($margin, 3);
    }

    public function testMaxLessMin(): void
    {
        $margin = $this->getTaskItemMargin(10, 9, 0);
        $this->validate($margin, 1);
    }

    public function testMinLessZero(): void
    {
        $margin = $this->getTaskItemMargin(-1, 1, 0);
        $this->validate($margin, 1);
    }

    public function testValid(): void
    {
        $margin = $this->getTaskItemMargin(0, 10, 0);
        $this->validate($margin, 0);
    }

    public function testValueLessZero(): void
    {
        $margin = $this->getTaskItemMargin(0, 1, -1);
        $this->validate($margin, 1);
    }

    public function testValues(): void
    {
        $margin = $this->getTaskItemMargin(0, 100, 10);
        self::assertEquals(0, $margin->getMinimum());
        self::assertEquals(100, $margin->getMaximum());
        self::assertEquals(10, $margin->getValue());

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
