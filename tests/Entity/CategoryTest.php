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

#[\PHPUnit\Framework\Attributes\CoversClass(Category::class)]
class CategoryTest extends AbstractEntityValidatorTestCase
{
    public function testCount(): void
    {
        $object = new Category();
        self::assertSame(0, $object->countProducts());
        self::assertSame(0, $object->countTasks());
        self::assertSame(0, $object->countItems());

        self::assertCount(0, $object->getProducts());
        self::assertCount(0, $object->getTasks());

        self::assertFalse($object->hasProducts());
        self::assertFalse($object->hasTasks());
    }

    public function testDescription(): void
    {
        $object = new Category();
        $object->setDescription('description');
        self::assertSame('description', $object->getDescription());
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
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
            $results = $this->validate($second, 1);
            $this->validatePaths($results, 'code');
        } finally {
            $this->deleteEntity($first);
            $this->deleteEntity($group);
        }
    }

    public function testGroup(): void
    {
        $category = new Category();
        self::assertNull($category->getGroup());
        self::assertNull($category->getGroupId());
        self::assertNull($category->getGroupCode());

        $group = new Group();
        $group->setCode('group');
        $category->setGroup($group);
        self::assertNotNull($category->getGroup());
        self::assertSame('group', $category->getGroupCode());
    }

    public function testInvalidCode(): void
    {
        $group = new Group();
        $group->setCode('group');
        $object = new Category();
        $object->setGroup($group);
        $results = $this->validate($object, 1);
        $this->validatePaths($results, 'code');
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
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

            $this->validate($second);
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testValid(): void
    {
        $group = new Group();
        $group->setCode('group');
        $object = new Category();
        $object->setCode('code');
        $object->setDescription('description');
        $object->setGroup($group);
        $this->validate($object);
    }
}
