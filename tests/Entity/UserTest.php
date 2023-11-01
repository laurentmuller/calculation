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

#[\PHPUnit\Framework\Attributes\CoversClass(User::class)]
class UserTest extends AbstractEntityValidatorTestCase
{
    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function testDuplicateBoth(): void
    {
        $first = new User();
        $first->setUsername('user')
            ->setPassword('password')
            ->setEmail('email@email.com');

        try {
            $this->saveEntity($first);
            $second = new User();
            $second->setUsername('user')
                ->setPassword('password')
                ->setEmail('email@email.com');
            $results = $this->validate($second, 2);
            $this->validatePaths($results, 'email', 'username');
        } finally {
            $this->deleteEntity($first);
        }
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function testDuplicateEmail(): void
    {
        $first = new User();
        $first->setUsername('user')
            ->setPassword('password')
            ->setEmail('email@email.com');

        try {
            $this->saveEntity($first);
            $second = new User();
            $second->setUsername('other')
                ->setPassword('password')
                ->setEmail('email@email.com');
            $results = $this->validate($second, 1);
            $this->validatePaths($results, 'email');
        } finally {
            $this->deleteEntity($first);
        }
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function testDuplicateUserName(): void
    {
        $first = new User();
        $first->setUsername('user')
            ->setPassword('password')
            ->setEmail('email@email.com');

        try {
            $this->saveEntity($first);
            $second = new User();
            $second->setUsername('user')
                ->setPassword('password')
                ->setEmail('other@email.com');
            $results = $this->validate($second, 1);
            $this->validatePaths($results, 'username');
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testInvalidAll(): void
    {
        $user = new User();
        $results = $this->validate($user, 3);
        $this->validatePaths($results, 'email', 'password', 'username');
    }

    public function testInvalidEmail(): void
    {
        $user = new User();
        $user->setUsername('user')
            ->setPassword('password');
        $this->validate($user, 1);
        $user->setEmail('invalid-email');
        $results = $this->validate($user, 1);
        $this->validatePaths($results, 'email');
    }

    public function testInvalidPassword(): void
    {
        $user = new User();
        $user->setUsername('user')
            ->setEmail('email@email.com');
        $results = $this->validate($user, 1);
        $this->validatePaths($results, 'password');
    }

    public function testInvalidUserName(): void
    {
        $user = new User();
        $user->setPassword('password')
            ->setEmail('email@email.com');
        $results = $this->validate($user, 1);
        $this->validatePaths($results, 'username');
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function testNotDuplicate(): void
    {
        $first = new User();
        $first->setUsername('user')
            ->setPassword('password')
            ->setEmail('email@email.com');

        try {
            $this->saveEntity($first);
            $second = new User();
            $second->setUsername('user 2')
                ->setPassword('password 2')
                ->setEmail('email2@email.com');
            $this->validate($second);
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testValid(): void
    {
        $user = new User();
        $user->setUsername('user')
            ->setPassword('password')
            ->setEmail('email@email.com');
        $this->validate($user);
    }
}
