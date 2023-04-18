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

#[\PHPUnit\Framework\Attributes\CoversClass(UserProperty::class)]
class UserPropertyTest extends AbstractTestEntityValidator
{
    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
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

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
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
            self::assertSame($expected->getString(), $actual->getString());
            self::assertSame($expected->getUser(), $actual->getUser());
        } finally {
            $this->deleteEntity($expected);
        }
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function testFindByUser(): void
    {
        $user = $this->getUser();
        self::assertNotNull($user);
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
        self::assertCount(3, $result);
    }

    public function testInvalidName(): void
    {
        $object = new UserProperty();
        $object->setString('value');
        $object->setUser($this->getUser());
        self::assertNotNull($this->validator);
        $result = $this->validator->validate($object);
        self::assertCount(1, $result);
    }

    public function testInvalidUser(): void
    {
        $object = new UserProperty('name');
        $object->setString('value');
        self::assertNotNull($this->validator);
        $result = $this->validator->validate($object);
        self::assertCount(1, $result);
    }

    public function testInvalidValue(): void
    {
        $object = new UserProperty('name');
        $object->setUser($this->getUser());
        self::assertNotNull($this->validator);
        $result = $this->validator->validate($object);
        self::assertCount(1, $result);
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
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
        self::assertCount(0, $result);
    }

    private function getRepository(): UserPropertyRepository
    {
        /** @psalm-var UserPropertyRepository $repository */
        $repository = $this->getManager()->getRepository(UserProperty::class);

        return $repository;
    }

    private function getUser(): ?User
    {
        return $this->getManager()->getRepository(User::class)->find(1);
    }
}
