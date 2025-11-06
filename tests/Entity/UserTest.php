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
use App\Utils\DateUtils;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Exception\MappingNotFoundException;
use Vich\UploaderBundle\Storage\StorageInterface;

final class UserTest extends EntityValidatorTestCase
{
    public function testAddProperty(): void
    {
        $user = new User();
        self::assertEmpty($user->getProperties());
        $property = new UserProperty();
        $user->addProperty($property);
        self::assertCount(1, $user->getProperties());
    }

    public function testCompare(): void
    {
        $item1 = new User();
        $item1->setUsername('User1');
        $item2 = new User();
        $item2->setUsername('User2');
        $actual = $item1->compare($item2);
        self::assertSame(-1, $actual);
    }

    public function testContainsProperty(): void
    {
        $user = new User();
        $property = new UserProperty();
        $user->addProperty($property);
        self::assertTrue($user->contains($property));
    }

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

    public function testEraseCredentials(): void
    {
        $user = new User();
        $user->eraseCredentials(); // @phpstan-ignore method.deprecated
        self::assertNull($user->getPassword());
    }

    public function testImageFile(): void
    {
        $user = new User();
        $user->setImageFile();
        self::assertNull($user->getImageFile());

        $file = new File(__FILE__, false);
        $user->setImageFile($file);
        self::assertSame($file, $user->getImageFile());
    }

    public function testImagePath(): void
    {
        $user = new User();
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('resolvePath')
            ->willReturn(null);
        self::assertNull($user->getImagePath($storage));

        $user->setImageName('file');
        self::assertNull($user->getImagePath($storage));

        $storage = $this->createMock(StorageInterface::class);
        $storage->method('resolvePath')
            ->willThrowException(new MappingNotFoundException());
        self::assertNull($user->getImagePath($storage));

        $file = __FILE__;
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('resolvePath')
            ->willReturn($file);
        self::assertSame($file, $user->getImagePath($storage));
    }

    public function testInitials(): void
    {
        $user = new User();
        $actual = $user->getInitials();
        self::assertSame('', $actual);

        $user->setUsername('username');
        $actual = $user->getInitials();
        self::assertSame('US', $actual);
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
     * @throws \Exception
     */
    public function testIsExpired(): void
    {
        $user = new User();
        self::assertTrue($user->isExpired());

        $expiresAt = new DatePoint();
        $expiresAt = DateUtils::sub($expiresAt, 'P7D');
        $user->setResetPasswordRequest($expiresAt, '', '');
        self::assertTrue($user->isExpired());

        $expiresAt = new DatePoint();
        $expiresAt = DateUtils::add($expiresAt, 'P7D');
        $expiresAt = DatePoint::createFromInterface($expiresAt);
        $user->setResetPasswordRequest($expiresAt, '', '');
        self::assertFalse($user->isExpired());
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
            $this->validate($second);
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testPasswordRequest(): void
    {
        $user = new User();
        $selector = 'selector';
        $hashedToken = 'hashedToken';
        $expiresAt = new DatePoint();
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
        self::assertEmpty($user->getProperties());
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
