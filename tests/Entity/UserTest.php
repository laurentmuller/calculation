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

/**
 * Unit test for {@link User} class.
 *
 * @author Laurent Muller
 */
class UserTest extends AbstractEntityValidatorTest
{
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

            $this->validate($second, 2);
        } finally {
            $this->deleteEntity($first);
        }
    }

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

            $this->validate($second, 1);
        } finally {
            $this->deleteEntity($first);
        }
    }

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

            $this->validate($second, 1);
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testInvalidAll(): void
    {
        $user = new User();
        $this->validate($user, 3);
    }

    public function testInvalidEmail(): void
    {
        $user = new User();
        $user->setUsername('user')
            ->setPassword('password');
        $this->validate($user, 1);

        $user->setEmail('fake-email');
        $this->validate($user, 1);
    }

    public function testInvalidPassword(): void
    {
        $user = new User();
        $user->setUsername('user')
            ->setEmail('email@email.com');
        $this->validate($user, 1);
    }

    public function testInvalidUserName(): void
    {
        $user = new User();
        $user->setPassword('password')
            ->setEmail('email@email.com');
        $this->validate($user, 1);
    }

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

            $this->validate($second, 0);
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
        $this->validate($user, 0);
    }
}
