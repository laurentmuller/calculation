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

#[\PHPUnit\Framework\Attributes\CoversClass(Group::class)]
class GroupTest extends AbstractEntityValidatorTestCase
{
    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function testDuplicate(): void
    {
        $first = new Group();
        $first->setCode('code');

        try {
            $this->saveEntity($first);
            $second = new Group();
            $second->setCode('code');
            $results = $this->validate($second, 1);
            $this->validatePaths($results, 'code');
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testFindMargin(): void
    {
        $group = new Group();
        $group->addMargin($this->createMargin());
        self::assertNotNull($group->findMargin(0));
        self::assertNull($group->findMargin(100));
    }

    public function testFindPercent(): void
    {
        $group = new Group();
        $group->addMargin($this->createMargin());
        self::assertEqualsWithDelta(1.1, $group->findPercent(50), 0.01);
        self::assertEqualsWithDelta(0, $group->findPercent(100), 0.01);
    }

    public function testGroupMargin(): void
    {
        $margin = $this->createMargin();
        self::assertTrue($margin->contains(0));
        self::assertFalse($margin->contains(100));
        self::assertEqualsWithDelta(11.0, $margin->getMarginAmount(10), 0.1);
    }

    public function testInvalidCode(): void
    {
        $object = new Group();
        $results = $this->validate($object, 1);
        $this->validatePaths($results, 'code');
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function testNotDuplicate(): void
    {
        $first = new Group();
        $first->setCode('code1');

        try {
            $this->saveEntity($first);
            $second = new Group();
            $second->setCode('code2');
            $this->validate($second);
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testValid(): void
    {
        $object = new Group();
        $object->setCode('code');
        $this->validate($object);
    }

    private function createMargin(): GroupMargin
    {
        $margin = new GroupMargin();
        $margin->setMinimum(0)
            ->setMaximum(100)
            ->setMargin(1.1);

        return $margin;
    }
}
