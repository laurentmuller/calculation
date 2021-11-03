<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Group;
use App\Entity\GroupMargin;

/**
 * Unit test for {@link App\Entity\Group} class.
 *
 * @author Laurent Muller
 */
class GroupTest extends AbstractEntityValidatorTest
{
    public function testDuplicate(): void
    {
        $first = new Group();
        $first->setCode('code');

        try {
            $this->saveEntity($first);

            $second = new Group();
            $second->setCode('code');

            $this->validate($second, 1);
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testFindMargin(): void
    {
        $group = new Group();
        $group->addMargin($this->createMargin(0, 100, 0.1));
        $this->assertNotNull($group->findMargin(0));
        $this->assertNull($group->findMargin(100));
    }

    public function testFindPercent(): void
    {
        $group = new Group();
        $group->addMargin($this->createMargin(0, 100, 0.1));
        $this->assertEqualsWithDelta(0.1, $group->findPercent(50), 0.01);
        $this->assertEqualsWithDelta(0, $group->findPercent(100), 0.01);
    }

    public function testGroupMargin(): void
    {
        $margin = $this->createMargin(0, 100, 0.1);
        $this->assertTrue($margin->contains(0));
        $this->assertFalse($margin->contains(100));
        $this->assertEqualsWithDelta(1.0, $margin->getMarginAmount(10), 0.1);
    }

    public function testInvalidCode(): void
    {
        $object = new Group();
        $this->validate($object, 1);
    }

    public function testNotDuplicate(): void
    {
        $first = new Group();
        $first->setCode('code');

        try {
            $this->saveEntity($first);

            $second = new Group();
            $second->setCode('code2');

            $this->validate($second, 0);
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testValid(): void
    {
        $object = new Group();
        $object->setCode('code');
        $this->validate($object, 0);
    }

    private function createMargin(float $minimum, float $maximum, float $margin): GroupMargin
    {
        $cm = new GroupMargin();
        $cm->setValues($minimum, $maximum, $margin);

        return $cm;
    }
}
