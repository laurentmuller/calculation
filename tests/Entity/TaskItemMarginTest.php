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

use App\Entity\TaskItemMargin;

/**
 * Unit test for {@link App\Entity\TaskItemMargin} class.
 *
 * @author Laurent Muller
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
        $this->assertEquals(0, $margin->getMinimum());
        $this->assertEquals(100, $margin->getMaximum());
        $this->assertEquals(10, $margin->getValue());

        $this->assertFalse($margin->contains(-1));
        $this->assertTrue($margin->contains(0));
        $this->assertTrue($margin->contains(99));
        $this->assertFalse($margin->contains(100));
    }

    private function getTaskItemMargin(float $minimum, float $maximum, float $value): TaskItemMargin
    {
        $entity = new TaskItemMargin();
        $entity->setMinimum($minimum)
            ->setMaximum($maximum)
            ->setValue($value);

        return $entity;
    }
}
