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

use App\Entity\Category;
use App\Entity\Group;

/**
 * Unit test for {@link App\Entity\Category} class.
 *
 * @author Laurent Muller
 */
class CategoryTest extends AbstractEntityValidatorTest
{
    public function testDuplicate(): void
    {
        $group = new Group();
        $group->setCode('group');
        $first = new Category();
        $first->setCode('code')
            ->setGroup($group);

        try {
            $this->saveEntity($group);
            $this->saveEntity($first);

            $second = new Category();
            $second->setCode('code')
                ->setGroup($group);

            $this->validate($second, 1);
        } finally {
            $this->deleteEntity($first);
            $this->deleteEntity($group);
        }
    }

    public function testInvalidCode(): void
    {
        $object = new Category();
        $this->validate($object, 1);
    }

    public function testNotDuplicate(): void
    {
        $group = new Group();
        $group->setCode('group');
        $first = new Category();
        $first->setCode('code')
            ->setGroup($group);

        try {
            $this->saveEntity($group);
            $this->saveEntity($first);

            $second = new Category();
            $second->setCode('code2')
                ->setGroup($group);

            $this->validate($second, 0);
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testValid(): void
    {
        $object = new Category();
        $object->setCode('code');
        $this->validate($object, 0);
    }
}
