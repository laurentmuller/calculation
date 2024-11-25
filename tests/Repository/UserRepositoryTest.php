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
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\QueryBuilder;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;

class UserRepositoryTest extends KernelServiceTestCase
{
    use DatabaseTrait;

    private UserRepository $repository;

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
            new \DateTimeImmutable(),
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
        self::assertCount(0, $actual);
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

    /**
     * @throws ORMException
     */
    public function testIsResettableUsers(): void
    {
        $actual = $this->repository->isResettableUsers();
        self::assertFalse($actual);
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
