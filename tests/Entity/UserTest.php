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

#[\PHPUnit\Framework\Attributes\CoversClass(User::class)]
class UserTest extends AbstractEntityValidatorTestCase
{
    public function testAddProperty(): void
    {
        $user = new User();
        self::assertCount(0, $user->getProperties());
        $property = new UserProperty();
        $user->addProperty($property);
        self::assertCount(1, $user->getProperties());
    }

    public function testAvatar(): void
    {
        $user = new User();

        $actual = $user->getAvatar();
        self::assertSame('https://robohash.org/?size=32x32', $actual);

        $actual = $user->getAvatar(16);
        self::assertSame('https://robohash.org/?size=16x16', $actual);

        $actual = $user->getAvatar(set: 2);
        self::assertSame('https://robohash.org/?size=32x32&set=set2', $actual);

        $actual = $user->getAvatar(background: 2);
        self::assertSame('https://robohash.org/?size=32x32&bgset=bg2', $actual);

        $actual = $user->getAvatar(32, 2, 2);
        self::assertSame('https://robohash.org/?size=32x32&set=set2&bgset=bg2', $actual);
    }

    public function testContainsProperty(): void
    {
        $user = new User();
        $property = new UserProperty();
        $user->addProperty($property);
        self::assertTrue($user->contains($property));
    }

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

    public function testEmailAddress(): void
    {
        $user = new User();
        $user->setUsername('user')
            ->setEmail('user@mail.com');
        self::assertSame('user', $user->getUsername());
        self::assertSame('user', $user->getUserIdentifier());
        self::assertSame('user@mail.com', $user->getEmail());
        self::assertSame('user (user@mail.com)', $user->getNameAndEmail());

        $address = $user->getEmailAddress();
        self::assertSame('user', $address->getName());
        self::assertSame('user@mail.com', $address->getAddress());
    }

    public function testEnabled(): void
    {
        $user = new User();
        self::assertTrue($user->isEnabled());
        $user->setEnabled(false);
        self::assertFalse($user->isEnabled());
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

    public function testPasswordRequest(): void
    {
        $user = new User();
        $expiresAt = new \DateTime();
        $selector = 'selector';
        $hashedToken = 'hashedToken';
        $user->setResetPasswordRequest($expiresAt, $selector, $hashedToken);
        self::assertSame($expiresAt, $user->getExpiresAt());
        self::assertSame($selector, $user->getSelector());
        self::assertSame($hashedToken, $user->getHashedToken());
        self::assertNotNull($user->getRequestedAt());
        self::assertTrue($user->isResetPassword());
        self::assertSame($user, $user->getUser());

        $user->eraseResetPasswordRequest();
        self::assertNotNull($user->getExpiresAt());
        self::assertNull($user->getSelector());
        self::assertSame('', $user->getHashedToken());
        self::assertNotNull($user->getRequestedAt());
        self::assertSame($user, $user->getUser());
        self::assertFalse($user->isResetPassword());
    }

    public function testRemoveProperty(): void
    {
        $user = new User();
        $property = new UserProperty();
        $user->addProperty($property);
        self::assertCount(1, $user->getProperties());
        $user->removeProperty($property);
        self::assertCount(0, $user->getProperties());
    }

    public function testSerialize(): void
    {
        $user = new User();
        $user->setUsername('user')
            ->setPassword('password');
        $values = $user->__serialize();
        self::assertCount(3, $values);
        self::assertNull($values[0]);
        self::assertSame('user', $values[1]);
        self::assertSame('password', $values[2]);
    }

    public function testUnserialize(): void
    {
        $values = [1, 'user', 'password'];
        $user = new User();
        $user->__unserialize($values);
        self::assertSame(1, $user->getId());
        self::assertSame('user', $user->getUsername());
        self::assertSame('password', $user->getPassword());
    }

    public function testUpdateLastLogin(): void
    {
        $user = new User();
        self::assertNull($user->getLastLogin());
        $user->updateLastLogin();
        self::assertNotNull($user->getLastLogin());
    }

    public function testValid(): void
    {
        $user = new User();
        $user->setUsername('user')
            ->setPassword('password')
            ->setEmail('email@email.com');
        $this->validate($user);
    }

    public function testVerified(): void
    {
        $user = new User();
        self::assertFalse($user->isVerified());
        $user->setVerified(true);
        self::assertTrue($user->isVerified());
    }
}
