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
use App\Tests\DatabaseTrait;
use App\Tests\Entity\IdTrait;
use App\Tests\KernelServiceTestCase;
use Doctrine\ORM\Exception\ORMException;

class UserPropertyRepositoryTest extends KernelServiceTestCase
{
    use DatabaseTrait;
    use IdTrait;

    private UserPropertyRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getService(UserPropertyRepository::class);
    }

    /**
     * @throws ORMException
     */
    public function testFindByUser(): void
    {
        $user = new User();
        $user->setUsername('test')
            ->setEmail('email@email.com')
            ->setPassword('password');
        $this->addEntity($user);

        $actual = $this->repository->findByUser($user);
        self::assertCount(0, $actual);

        $property = new UserProperty();
        $property->setUser($user)
            ->setName('name')
            ->setValue('value');
        $this->addEntity($property);

        $actual = $this->repository->findByUser($user);
        self::assertCount(1, $actual);
    }

    /**
     * @throws ORMException
     */
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
}
