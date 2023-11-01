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

#[\PHPUnit\Framework\Attributes\CoversClass(GroupMargin::class)]
class GroupMarginsTest extends AbstractEntityValidatorTestCase
{
    public function testInvalidMargin(): void
    {
        $group = $this->createGroup();
        $this->addMargin($group, 0, 100, 0.0);
        $results = $this->validate($group, 1);
        $this->validatePaths($results, 'margins[0].margin');
    }

    public function testInvalidMaximum(): void
    {
        $group = $this->createGroup();
        $this->addMargin($group, 0, 100, 1.1);
        $this->addMargin($group, 100, 99, 1.2);
        $results = $this->validate($group, 2);
        $this->validatePaths($results, 'margins[1].maximum', 'margins[1].maximum');
    }

    public function testInvalidMinimum(): void
    {
        $group = $this->createGroup();
        $this->addMargin($group, 0, 100, 1.1);
        $this->addMargin($group, 99, 200, 1.2);
        $results = $this->validate($group, 1);
        $this->validatePaths($results, 'margins[1].minimum');
    }

    public function testValid(): void
    {
        $group = $this->createGroup();
        $this->addMargin($group, 0, 100, 1.1);
        $this->addMargin($group, 100, 200, 1.2);
        $this->validate($group);
    }

    private function addMargin(Group $group, float $minimum, float $maximum, float $margin): void
    {
        $groupMargin = new GroupMargin();
        $groupMargin->setMinimum($minimum)
            ->setMaximum($maximum)
            ->setMargin($margin);
        $group->addMargin($groupMargin);
    }

    private function createGroup(): Group
    {
        $group = new Group();
        $group->setCode('code');

        return $group;
    }
}
