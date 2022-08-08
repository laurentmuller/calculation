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

use App\Entity\User;
use App\Entity\UserProperty;
use App\Repository\UserPropertyRepository;

/**
 * Unit test for {@link UserProperty} class.
 */
class UserPropertyTest extends AbstractEntityValidatorTest
{
    public function testDuplicate(): void
    {
        $first = new UserProperty('name');
        $first->setString('value');
        $first->setUser($this->getUser());

        try {
            $this->saveEntity($first);

            $second = new UserProperty('name');
            $second->setValue('value');
            $first->setUser($this->getUser());

            $this->validate($second, 1);
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testFindByName(): void
    {
        $user = $this->getUser();
        $expected = new UserProperty('name');
        $expected->setString('value');
        $expected->setUser($user);

        try {
            $this->saveEntity($expected);
            $actual = $this->getRepository()->findByName($user, 'name');
            self::assertNotNull($actual);
            self::assertEquals($expected->getName(), $actual->getName());
            self::assertEquals($expected->getString(), $actual->getString());
            self::assertEquals($expected->getUser(), $actual->getUser());
        } finally {
            $this->deleteEntity($expected);
        }
    }

    public function testFindByUser(): void
    {
        $user = $this->getUser();
        $expected = new UserProperty('name');
        $expected->setString('value');
        $expected->setUser($user);

        try {
            $actual = $this->getRepository()->findByUser($user);
            self::assertCount(0, $actual);

            $this->saveEntity($expected);

            $actual = $this->getRepository()->findByUser($user);
            self::assertCount(1, $actual);
        } finally {
            $this->deleteEntity($expected);
        }
    }

    public function testInvalidAll(): void
    {
        $object = new UserProperty();
        $result = $this->validator->validate($object);
        self::assertEquals(3, $result->count());
    }

    public function testInvalidName(): void
    {
        $object = new UserProperty();
        $object->setString('value');
        $object->setUser($this->getUser());
        $result = $this->validator->validate($object);
        self::assertEquals(1, $result->count());
    }

    public function testInvalidUser(): void
    {
        $object = new UserProperty('name');
        $object->setString('value');
        $result = $this->validator->validate($object);
        self::assertEquals(1, $result->count());
    }

    public function testInvalidValue(): void
    {
        $object = new UserProperty('name');
        $object->setUser($this->getUser());
        $result = $this->validator->validate($object);
        self::assertEquals(1, $result->count());
    }

    public function testNotDuplicate(): void
    {
        $user = $this->getUser();
        $first = new UserProperty('name1');
        $first->setString('value');
        $first->setUser($user);

        try {
            $this->saveEntity($first);

            $second = new UserProperty('name2');
            $second->setValue('value');
            $second->setUser($user);

            $this->validate($second, 0);
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testValid(): void
    {
        $object = new UserProperty('name');
        $object->setString('value');
        $object->setUser($this->getUser());
        $result = $this->validator->validate($object);
        self::assertEquals(0, $result->count());
    }

    private function getRepository(): UserPropertyRepository
    {
        return $this->getManager()->getRepository(UserProperty::class);
    }

    private function getUser(): User
    {
        return $this->getManager()->getRepository(User::class)->find(1);
    }
}
