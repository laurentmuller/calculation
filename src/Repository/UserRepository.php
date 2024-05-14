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

namespace App\Repository;

use App\Entity\User;
use App\Interfaces\RoleInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Persistence\Repository\ResetPasswordRequestRepositoryTrait;
use SymfonyCasts\Bundle\ResetPassword\Persistence\ResetPasswordRequestRepositoryInterface;

/**
 * Repository for user entity.
 *
 * @template-extends AbstractRepository<User>
 *
 * @implements PasswordUpgraderInterface<User>
 */
class UserRepository extends AbstractRepository implements PasswordUpgraderInterface, ResetPasswordRequestRepositoryInterface
{
    use ResetPasswordRequestRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @see ResetPasswordRequestRepositoryInterface
     *
     * @psalm-param User&object $user
     */
    public function createResetPasswordRequest(
        object $user,
        \DateTimeInterface $expiresAt,
        string $selector,
        string $hashedToken
    ): ResetPasswordRequestInterface {
        return $user->setResetPasswordRequest(
            \DateTimeImmutable::createFromInterface($expiresAt),
            $selector,
            $hashedToken
        );
    }

    /**
     * Finds a user by their email.
     */
    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Finds a user by their username.
     */
    public function findByUsername(string $username): ?User
    {
        return $this->findOneBy(['username' => $username]);
    }

    /**
     * Finds a user by their username or email.
     */
    public function findByUsernameOrEmail(string $usernameOrEmail): ?User
    {
        if (false !== \filter_var($usernameOrEmail, \FILTER_VALIDATE_EMAIL)) {
            return $this->findByEmail($usernameOrEmail);
        }

        return $this->findByUsername($usernameOrEmail);
    }

    /**
     * @see ResetPasswordRequestRepositoryInterface
     *
     * @psalm-param User&object $user
     */
    public function getMostRecentNonExpiredRequestDate(object $user): ?\DateTimeInterface
    {
        return $user->isExpired() ? null : $user->getRequestedAt();
    }

    /**
     * Gets users where reset password was requested.
     *
     * @return User[]
     */
    public function getResettableUsers(): array
    {
        return $this->createResettableQueryBuilder()
            ->orderBy('e.username')
            ->getQuery()
            ->getResult();
    }

    /**
     * Gets the query builder for the list of users sorted by the username.
     *
     * @param literal-string $alias the entity alias
     */
    public function getSortedBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        $field = $this->getSortField('username', $alias);

        return $this->createQueryBuilder($alias)
            ->orderBy($field, self::SORT_ASC);
    }

    public function getSortField(string $field, string $alias = self::DEFAULT_ALIAS): string
    {
        return match ($field) {
            'enabled' => "IFELSE($alias.$field = 1, 0, 1)", // reverse
            'role' => "SUBSTRING(IFNULL($alias.$field, 'ROLE_USER'), 5)",
            default => parent::getSortField($field, $alias),
        };
    }

    /**
     * Returns the criteria clause to filter users where the role name is not the super-administrator role name.
     */
    public function getSuperAdminFilter(string $alias = self::DEFAULT_ALIAS): string
    {
        return \sprintf(
            "IFNULL(%s.role, '%s') <> '%s'",
            $alias,
            RoleInterface::ROLE_USER,
            RoleInterface::ROLE_SUPER_ADMIN
        );
    }

    /**
     * Gets the query builder for the table.
     *
     * @param literal-string $alias the entity alias
     */
    public function getTableQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->select("$alias.id")
            ->addSelect("$alias.imageName")
            ->addSelect("$alias.username")
            ->addSelect("$alias.email")
            ->addSelect("$alias.role")
            ->addSelect("$alias.enabled")
            ->addSelect("$alias.lastLogin")
            ->addSelect("$alias.hashedToken");
    }

    /**
     * Returns if one or more users have reset password requested.
     */
    public function isResettableUsers(): bool
    {
        try {
            return 0 !== $this->createResettableQueryBuilder()
                ->select('COUNT(e.id)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (\Doctrine\ORM\Exception\ORMException) {
            return false;
        }
    }

    /**
     * @see ResetPasswordRequestRepositoryInterface
     */
    public function removeExpiredResetPasswordRequests(): int
    {
        $time = new \DateTimeImmutable('-1 week');
        $query = $this->createQueryBuilder('e')
            ->update()
            ->set('e.selector', 'NULL')
            ->set('e.hashedToken', 'NULL')
            ->set('e.requestedAt', 'NULL')
            ->set('e.expiresAt', 'NULL')
            ->where('e.expiresAt <= :time')
            ->setParameter('time', $time, Types::DATETIME_IMMUTABLE)
            ->getQuery();

        return (int) $query->execute();
    }

    /**
     * @see ResetPasswordRequestRepositoryInterface
     *
     * @psalm-param User&object $resetPasswordRequest
     */
    public function removeResetPasswordRequest(ResetPasswordRequestInterface $resetPasswordRequest): void
    {
        $this->resetPasswordRequest($resetPasswordRequest);
    }

    /**
     * Remove the reset password request.
     *
     * @param User|User[] $users the user or users to reset
     */
    public function resetPasswordRequest(User|array $users): void
    {
        if (!\is_array($users)) {
            $users = [$users];
        }
        foreach ($users as $user) {
            $user->eraseResetPasswordRequest();
        }

        $this->flush();
    }

    /**
     * @see PasswordUpgraderInterface
     *
     * @psalm-param User $user
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        $user->setPassword($newHashedPassword);
        $this->flush();
    }

    private function createResettableQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('e')
            ->where('e.hashedToken IS NOT NULL');
    }
}
