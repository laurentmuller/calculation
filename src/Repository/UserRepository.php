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
use App\Utils\DateUtils;
use App\Utils\StringUtils;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\DatePointType;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Persistence\Repository\ResetPasswordRequestRepositoryTrait;
use SymfonyCasts\Bundle\ResetPassword\Persistence\ResetPasswordRequestRepositoryInterface;

/**
 * Repository for user entity.
 *
 * @extends AbstractRepository<User>
 *
 * @implements UserProviderInterface<User>
 */
class UserRepository extends AbstractRepository implements PasswordUpgraderInterface, ResetPasswordRequestRepositoryInterface, UserProviderInterface
{
    use ResetPasswordRequestRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @see ResetPasswordRequestRepositoryInterface
     *
     * @phpstan-param User $user
     */
    #[\Override]
    public function createResetPasswordRequest(
        object $user,
        \DateTimeInterface $expiresAt,
        string $selector,
        string $hashedToken
    ): ResetPasswordRequestInterface {
        return $user->setResetPasswordRequest(DateUtils::toDatePoint($expiresAt), $selector, $hashedToken);
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
     * @phpstan-param User $user
     */
    #[\Override]
    public function getMostRecentNonExpiredRequestDate(object $user): ?DatePoint
    {
        return $user->isExpired() ? null : $user->getRequestedAt();
    }

    /**
     * Gets users where the reset password was requested.
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
     * @param string $alias the entity alias
     */
    public function getSortedBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        $field = $this->getSortField('username', $alias);

        return $this->createQueryBuilder($alias)
            ->orderBy($field, self::SORT_ASC);
    }

    #[\Override]
    public function getSortField(string $field, string $alias = self::DEFAULT_ALIAS): string
    {
        return match ($field) {
            'enabled' => \sprintf('IFELSE(%s.%s = 1, 0, 1)', $alias, $field), // reverse
            'role' => \sprintf("SUBSTRING(IFNULL(%s.%s, 'ROLE_USER'), 5)", $alias, $field),
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
     * @param string $alias the entity alias
     */
    public function getTableQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->select($alias . '.id')
            ->addSelect($alias . '.imageName')
            ->addSelect($alias . '.username')
            ->addSelect($alias . '.email')
            ->addSelect($alias . '.role')
            ->addSelect($alias . '.enabled')
            ->addSelect($alias . '.lastLogin')
            ->addSelect($alias . '.hashedToken')
            ->addSelect(\sprintf('UPPER(SUBSTRING(%s.username, 1, 2)) as initials', $alias));
    }

    /**
     * Returns if one or more users have the reset password requested.
     */
    public function isResettableUsers(): bool
    {
        return 0 !== $this->createResettableQueryBuilder()
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @see UserProviderInterface
     */
    #[\Override]
    public function loadUserByIdentifier(string $identifier): User
    {
        $user = $this->findByUsername($identifier);
        if (!$user instanceof User) {
            throw $this->createUserNotFoundException($identifier);
        }

        return $user;
    }

    /**
     * @see UserProviderInterface
     */
    #[\Override]
    public function refreshUser(UserInterface $user): User
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', StringUtils::getDebugType($user)));
        }

        return $this->loadUserByIdentifier($user->getUsername());
    }

    /**
     * @see ResetPasswordRequestRepositoryInterface
     */
    #[\Override]
    public function removeExpiredResetPasswordRequests(): int
    {
        $time = DateUtils::createDatePoint('-1 week');
        $query = $this->createQueryBuilder('e')
            ->update()
            ->set('e.selector', 'NULL')
            ->set('e.hashedToken', 'NULL')
            ->set('e.requestedAt', 'NULL')
            ->set('e.expiresAt', 'NULL')
            ->where('e.expiresAt <= :time')
            ->setParameter('time', $time, DatePointType::NAME)
            ->getQuery();

        return (int) $query->execute();
    }

    /**
     * @see ResetPasswordRequestRepositoryInterface
     *
     * @phpstan-param User $resetPasswordRequest
     */
    #[\Override]
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
     * @see UserProviderInterface
     */
    #[\Override]
    public function supportsClass(string $class): bool
    {
        return User::class === $class;
    }

    /**
     * @see PasswordUpgraderInterface
     *
     * @phpstan-param User $user
     */
    #[\Override]
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

    private function createUserNotFoundException(string $identifier): UserNotFoundException
    {
        $e = new UserNotFoundException(\sprintf('User "%s" not found.', $identifier));
        $e->setUserIdentifier($identifier);

        return $e;
    }
}
