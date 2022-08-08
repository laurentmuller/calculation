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

use App\Entity\Group;
use App\Entity\GroupMargin;

/**
 * Unit test for {@link GroupMargin} class.
 */
class GroupMarginsTest extends AbstractEntityValidatorTest
{
    public function testInvalidMaximum(): void
    {
        $group = $this->createGroup();
        $group->addMargin($this->createMargin(0, 100, 0.1));
        $group->addMargin($this->createMargin(100, 99, 0.2));

        $results = $this->validate($group, 2);
        $path = $results->get(0)->getPropertyPath();
        self::assertEquals('margins[1].maximum', $path);
    }

    public function testInvalidMinimum(): void
    {
        $group = $this->createGroup();
        $group->addMargin($this->createMargin(0, 100, 0.1));
        $group->addMargin($this->createMargin(99, 200, 0.2));

        $results = $this->validate($group, 1);
        $path = $results->get(0)->getPropertyPath();
        self::assertEquals('margins[1].minimum', $path);
    }

    public function testValid(): void
    {
        $group = $this->createGroup();
        $group->addMargin($this->createMargin(0, 100, 0.1));
        $group->addMargin($this->createMargin(100, 200, 0.2));
        $this->validate($group, 0);
    }

    private function createGroup(): Group
    {
        $group = new Group();
        $group->setCode('code');

        return $group;
    }

    private function createMargin(float $minimum, float $maximum, float $margin): GroupMargin
    {
        $entity = new GroupMargin();
        $entity->setValues($minimum, $maximum, $margin);

        return $entity;
    }
}
