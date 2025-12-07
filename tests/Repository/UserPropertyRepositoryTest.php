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

namespace App\Tests\Repository;

use App\Entity\User;
use App\Entity\UserProperty;
use App\Repository\UserPropertyRepository;
use App\Tests\Entity\IdTrait;

/**
 * @extends AbstractRepositoryTestCase<UserProperty, UserPropertyRepository>
 */
final class UserPropertyRepositoryTest extends AbstractRepositoryTestCase
{
    use IdTrait;

    public function testFindByUser(): void
    {
        $user = new User();
        $user->setUsername('test')
            ->setEmail('email@email.com')
            ->setPassword('password');
        $this->addEntity($user);

        $actual = $this->repository->findByUser($user);
        self::assertEmpty($actual);

        $property = new UserProperty();
        $property->setUser($user)
            ->setName('name')
            ->setValue('value');
        $this->addEntity($property);

        $actual = $this->repository->findByUser($user);
        self::assertCount(1, $actual);
    }

    public function testFindOneByUserAndName(): void
    {
        $user = new User();
        $user->setUsername('test')
            ->setEmail('email@email.com')
            ->setPassword('password');
        $this->addEntity($user);

        $actual = $this->repository->findOneByUserAndName($user, 'name');
        self::assertNull($actual);

        $property = new UserProperty();
        $property->setUser($user)
            ->setName('name')
            ->setValue('value');
        $this->addEntity($property);
        $actual = $this->repository->findOneByUserAndName($user, 'name');
        self::assertNotNull($actual);
    }

    #[\Override]
    protected function getRepositoryClass(): string
    {
        return UserPropertyRepository::class;
    }
}
