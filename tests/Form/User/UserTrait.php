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

namespace App\Tests\Form\User;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Entity\IdTrait;
use App\Tests\Form\ManagerRegistryTrait;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

trait UserTrait
{
    use IdTrait;
    use ManagerRegistryTrait;

    private ?User $user = null;

    /**
     * @throws \ReflectionException
     */
    protected function getUser(): User
    {
        if (!$this->user instanceof User) {
            $this->user = new User();
            $this->user->setUsername('username')
                ->setEmail('email@email.com')
                ->setPassword('password');

            return self::setId($this->user);
        }

        return $this->user;
    }

    /**
     * @throws Exception|\ReflectionException
     */
    protected function getUserEntityType(): EntityType
    {
        return new EntityType($this->getUserRegistry());
    }

    /**
     * @throws Exception|\ReflectionException
     */
    protected function getUserRegistry(): MockObject&ManagerRegistry
    {
        return $this->createManagerRegistry(
            User::class,
            UserRepository::class,
            'getSortedBuilder',
            [$this->getUser()]
        );
    }
}
