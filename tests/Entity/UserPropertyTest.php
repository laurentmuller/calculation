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

class UserPropertyTest extends EntityValidatorTestCase
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
            $results = $this->validate($second, 1);
            $this->validatePaths($results, 'user');
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testFindByName(): void
    {
        $user = $this->getUser();
        self::assertNotNull($user);
        $expected = new UserProperty('name');
        $expected->setString('value');
        $expected->setUser($user);

        try {
            $this->saveEntity($expected);
            $actual = $this->getRepository()->findOneByUserAndName($user, 'name');
            self::assertNotNull($actual);
            self::assertSame($expected->getName(), $actual->getName());
            self::assertSame($expected->getValue(), $actual->getValue());
            self::assertSame($expected->getUser(), $actual->getUser());
        } finally {
            $this->deleteEntity($expected);
        }
    }

    public function testFindByUser(): void
    {
        $user = $this->getUser();
        self::assertNotNull($user);
        $expected = new UserProperty('name');
        $expected->setString('value');
        $expected->setUser($user);

        try {
            $actual = $this->getRepository()->findByUser($user);
            self::assertEmpty($actual);
            $this->saveEntity($expected);
            $actual = $this->getRepository()->findByUser($user);
            self::assertCount(1, $actual);
        } finally {
            $this->deleteEntity($expected);
        }
    }

    public function testInstance(): void
    {
        $user = $this->getUser();
        self::assertNotNull($user);
        $property = UserProperty::instance('name', $user);
        $property->setValue('value');
        self::assertSame('name', $property->getName());
        self::assertSame('value', $property->getValue());
    }

    public function testInvalidAll(): void
    {
        $object = new UserProperty();
        $results = $this->validate($object, 3);
        $this->validatePaths($results, 'user', 'name', 'value');
    }

    public function testInvalidName(): void
    {
        $object = new UserProperty();
        $object->setString('value');
        $object->setUser($this->getUser());
        $results = $this->validate($object, 1);
        $this->validatePaths($results, 'name');
    }

    public function testInvalidUser(): void
    {
        $object = new UserProperty('name');
        $object->setString('value');
        $results = $this->validate($object, 1);
        $this->validatePaths($results, 'user');
    }

    public function testInvalidValue(): void
    {
        $object = new UserProperty('name');
        $object->setUser($this->getUser());
        $results = $this->validate($object, 1);
        $this->validatePaths($results, 'value');
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
            $this->validate($second);
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testValid(): void
    {
        $object = new UserProperty('name');
        $object->setString('value');
        $object->setUser($this->getUser());
        $this->validate($object);
    }

    private function getRepository(): UserPropertyRepository
    {
        /** @phpstan-var UserPropertyRepository  */
        return $this->getManager()->getRepository(UserProperty::class); // @phpstan-ignore varTag.type
    }

    private function getUser(): ?User
    {
        return $this->getManager()->getRepository(User::class)->find(1);
    }
}
