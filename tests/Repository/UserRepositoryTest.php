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
use App\Repository\UserRepository;
use App\Tests\DatabaseTrait;
use App\Tests\KernelServiceTestCase;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;

final class UserRepositoryTest extends KernelServiceTestCase
{
    use DatabaseTrait;

    private UserRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->getService(UserRepository::class);
    }

    public function testCreateResetPasswordRequest(): void
    {
        $user = $this->getUser();
        $actual = $this->repository->createResetPasswordRequest(
            $user,
            new DatePoint(),
            'selector',
            'hashedToken'
        );
        self::assertInstanceOf(ResetPasswordRequestInterface::class, $actual);
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
        self::assertInstanceOf(QueryBuilder::class, $actual);
    }

    public function testGetSortFields(): void
    {
        $actual = $this->repository->getSortField('enabled');
        self::assertSame('IFELSE(e.enabled = 1, 0, 1)', $actual);

        $actual = $this->repository->getSortField('role');
        self::assertSame("SUBSTRING(IFNULL(e.role, 'ROLE_USER'), 5)", $actual);
    }

    public function testGetSuperAdminFilter(): void
    {
        $actual = $this->repository->getSuperAdminFilter();
        self::assertSame("IFNULL(e.role, 'ROLE_USER') <> 'ROLE_SUPER_ADMIN'", $actual);
    }

    public function testGetTableQueryBuilder(): void
    {
        $actual = $this->repository->getTableQueryBuilder();
        self::assertInstanceOf(QueryBuilder::class, $actual);
    }

    public function testIsResettableUsers(): void
    {
        $actual = $this->repository->isResettableUsers();
        self::assertFalse($actual);
    }

    public function testLoadUserByIdentifier(): void
    {
        $user = $this->repository->loadUserByIdentifier('ROLE_USER');
        self::assertInstanceOf(User::class, $user);
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
        self::assertInstanceOf(User::class, $refreshedUser);
    }

    public function testRefreshUserException(): void
    {
        self::expectException(UnsupportedUserException::class);
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

    private function getUser(): User
    {
        $user = $this->repository->find(['id' => 3]);
        self::assertInstanceOf(User::class, $user);

        return $user;
    }
}
