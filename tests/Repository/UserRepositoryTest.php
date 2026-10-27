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

use App\Doctrine\WebmasterFilter;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @extends AbstractRepositoryTestCase<User, UserRepository>
 */
final class UserRepositoryTest extends AbstractRepositoryTestCase
{
    public function testCreateResetPasswordRequest(): void
    {
        $date = new DatePoint();
        $user = $this->getUser();
        $actual = $this->repository->createResetPasswordRequest(
            $user,
            $date,
            'selector',
            'hashedToken'
        );
        self::assertSame($date, $actual->getExpiresAt());
        self::assertSame('selector', $actual->getSelector());
        self::assertSame('hashedToken', $actual->getHashedToken());
    }

    public function testEnableWebmasterFilter(): void
    {
        $manager = $this->getService(EntityManagerInterface::class);
        $filters = $manager->getFilters();

        self::assertTrue($filters->has(WebmasterFilter::NAME));
        self::assertFalse($filters->isEnabled(WebmasterFilter::NAME));

        $this->repository->enableWebmasterFilter();
        self::assertTrue($filters->isEnabled(WebmasterFilter::NAME));
    }

    public function testFindByEmail(): void
    {
        $actual = $this->repository->findByEmail('email@email.com');
        self::assertNull($actual);

        $actual = $this->repository->findByEmail('ROLE_USER@TEST.COM');
        self::assertNotNull($actual);
    }

    public function testFindByUserName(): void
    {
        $actual = $this->repository->findByUsername('test');
        self::assertNull($actual);

        $actual = $this->repository->findByUsername('ROLE_USER');
        self::assertNotNull($actual);
    }

    public function testFindByUserNameOrEmail(): void
    {
        $actual = $this->repository->findByUsernameOrEmail('test');
        self::assertNull($actual);
        $actual = $this->repository->findByUsernameOrEmail('email@email.com');
        self::assertNull($actual);

        $actual = $this->repository->findByUsernameOrEmail('ROLE_USER');
        self::assertNotNull($actual);
        $actual = $this->repository->findByUsernameOrEmail('ROLE_USER@TEST.COM');
        self::assertNotNull($actual);
    }

    public function testGetMostRecentNonExpiredRequestDate(): void
    {
        $user = $this->getUser();
        $actual = $this->repository->getMostRecentNonExpiredRequestDate($user);
        self::assertNull($actual);
    }

    public function testGetResettableUsers(): void
    {
        $actual = $this->repository->getResettableUsers();
        self::assertEmpty($actual);
    }

    public function testGetSortedBuilder(): void
    {
        $actual = $this->repository->getSortedBuilder();
        self::assertNotNull($actual->getDQLPart('orderBy'));
    }

    public function testGetSortFields(): void
    {
        $this->assertSameSortField('enabled', 'IFELSE(e.enabled = 1, 0, 1)');
        $this->assertSameSortField('role', "SUBSTRING(IFNULL(e.role, 'ROLE_USER'), 5)");
    }

    public function testGetTableQueryBuilder(): void
    {
        $actual = $this->repository->getTableQueryBuilder();
        self::assertNotNull($actual->getDQLPart('select'));
    }

    public function testIsResettableUsers(): void
    {
        $actual = $this->repository->isResettableUsers();
        self::assertFalse($actual);
    }

    public function testLoadUserByIdentifier(): void
    {
        $user = $this->repository->loadUserByIdentifier('ROLE_USER');
        self::assertSame('ROLE_USER', $user->getUserIdentifier());
    }

    public function testLoadUserByIdentifierException(): void
    {
        self::expectException(UserNotFoundException::class);
        $this->repository->loadUserByIdentifier('FAKE');
    }

    public function testRefreshUser(): void
    {
        $user = $this->getUser();
        $refreshedUser = $this->repository->refreshUser($user);
        self::assertSame('ROLE_USER', $refreshedUser->getUserIdentifier());
    }

    public function testRefreshUserException(): void
    {
        self::expectException(UnsupportedUserException::class);
        self::expectExceptionMessageIsOrContains('Instances of "' . InMemoryUser::class . '" are not supported.');
        $user = new InMemoryUser('username', 'password', ['ROLE_USER']);
        $this->repository->refreshUser($user);
    }

    public function testRemoveExpiredResetPasswordRequests(): void
    {
        $actual = $this->repository->removeExpiredResetPasswordRequests();
        self::assertSame(0, $actual);
    }

    public function testRemoveResetPasswordRequest(): void
    {
        $user = $this->getUser();
        $this->repository->removeResetPasswordRequest($user);
        self::assertTrue($user->isExpired());
    }

    public function testSupportsClass(): void
    {
        self::assertTrue($this->repository->supportsClass(User::class));
        self::assertFalse($this->repository->supportsClass(InMemoryUser::class));
    }

    public function testUpgradePassword(): void
    {
        $user = $this->getUser();
        $oldPassword = $user->getPassword();
        $this->repository->upgradePassword($user, (string) $oldPassword);
        $newPassword = $user->getPassword();
        self::assertSame($oldPassword, $newPassword);
    }

    public function testUpgradePasswordInvalid(): void
    {
        $user = new class implements PasswordAuthenticatedUserInterface {
            #[\Override]
            public function getPassword(): ?string
            {
                return null;
            }
        };
        self::expectException(UnsupportedUserException::class);
        self::expectExceptionMessageIsOrContains('Instances of "' . PasswordAuthenticatedUserInterface::class . '@anonymous" are not supported.');
        $this->repository->upgradePassword($user, 'fake');
    }

    #[\Override]
    protected function getRepositoryClass(): string
    {
        return UserRepository::class;
    }

    private function getUser(): User
    {
        $user = $this->repository->find(['id' => 3]);
        self::assertInstanceOf(User::class, $user);

        return $user;
    }
}
