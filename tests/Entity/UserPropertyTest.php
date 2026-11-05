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

final class UserPropertyTest extends EntityValidatorTestCase
{
    public function testDuplicate(): void
    {
        $first = new UserProperty('name');
        $first->withString('value');
        $first->withUser($this->getUser());

        try {
            $this->saveEntity($first);
            $second = new UserProperty('name');
            $second->withValue('value');
            $first->withUser($this->getUser());
            $results = $this->validate($second, 1);
            $this->validatePaths($results, 'user');
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testFindByUser(): void
    {
        $user = $this->getUser();
        self::assertNotNull($user);
        $expected = new UserProperty('name');
        $expected->withString('value');
        $expected->withUser($user);

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
        $property->withValue('value');
        self::assertSame('name', $property->name);
        self::assertSame('value', $property->value);
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
        $object->withString('value');
        $object->withUser($this->getUser());
        $results = $this->validate($object, 1);
        $this->validatePaths($results, 'name');
    }

    public function testInvalidUser(): void
    {
        $object = new UserProperty('name');
        $object->withString('value');
        $results = $this->validate($object, 1);
        $this->validatePaths($results, 'user');
    }

    public function testInvalidValue(): void
    {
        $object = new UserProperty('name');
        $object->withUser($this->getUser());
        $results = $this->validate($object, 1);
        $this->validatePaths($results, 'value');
    }

    public function testNotDuplicate(): void
    {
        $user = $this->getUser();
        $first = new UserProperty('name1');
        $first->withString('value');
        $first->withUser($user);

        try {
            $this->saveEntity($first);
            $second = new UserProperty('name2');
            $second->withValue('value');
            $second->withUser($user);
            $this->validate($second);
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testValid(): void
    {
        $object = new UserProperty('name');
        $object->withString('value');
        $object->withUser($this->getUser());
        $this->validate($object);
    }

    private function getRepository(): UserPropertyRepository
    {
        return $this->getManager()->getRepository(UserProperty::class);
    }

    private function getUser(): ?User
    {
        return $this->getManager()->getRepository(User::class)->find(1);
    }
}
