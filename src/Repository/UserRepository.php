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
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
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
     * @throws UnsupportedUserException
     */
    public function createResetPasswordRequest(object $user, \DateTimeInterface $expiresAt, string $selector, string $hashedToken): ResetPasswordRequestInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $user::class));
        }
        $expiresAt = \DateTimeImmutable::createFromInterface($expiresAt);

        return $user->setResetPasswordRequest($expiresAt, $selector, $hashedToken);
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
     */
    public function getMostRecentNonExpiredRequestDate(object $user): ?\DateTimeInterface
    {
        if ($user instanceof User && !$user->isExpired()) {
            return $user->getRequestedAt();
        }

        return null;
    }

    /**
     * Gets users where reset password was requested.
     *
     * @return User[]
     */
    public function getResettableUsers(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.hashedToken IS NOT NULL')
            ->orderBy('e.username')
            ->getQuery()
            ->getResult();
    }

    /**
     * Gets the query builder for the list of users sorted by username.
     *
     * @param literal-string $alias the entity alias
     */
    public function getSortedBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        $field = $this->getSortField('username', $alias);

        return $this->createQueryBuilder($alias)
            ->orderBy($field, Criteria::ASC);
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
     * Returns the criteria clause to filter user where the role name is not the super administrator role name.
     */
    public function getSuperAdminFilter(string $alias = self::DEFAULT_ALIAS): string
    {
        return \sprintf("IFNULL(%s.role, '%s') <> '%s'", $alias, RoleInterface::ROLE_USER, RoleInterface::ROLE_SUPER_ADMIN);
    }

    /**
     * Returns if one or more users have reset password requested.
     */
    public function isResettableUsers(): bool
    {
        try {
            return 0 !== (int) $this->createQueryBuilder('e')
                ->select('COUNT(e.id)')
                ->where('e.hashedToken IS NOT NULL')
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
        $query = $this->createQueryBuilder('u')
            ->update()
            ->set('u.selector', 'NULL')
            ->set('u.hashedToken', 'NULL')
            ->set('u.requestedAt', 'NULL')
            ->set('u.expiresAt', 'NULL')
            ->where('u.expiresAt <= :time')
            ->setParameter('time', $time, Types::DATETIME_IMMUTABLE)
            ->getQuery();

        return (int) $query->execute();
    }

    /**
     * @see ResetPasswordRequestRepositoryInterface
     *
     * @throws UnsupportedUserException
     */
    public function removeResetPasswordRequest(ResetPasswordRequestInterface $resetPasswordRequest): void
    {
        if (!$resetPasswordRequest instanceof User) {
            throw new UnsupportedUserException(\sprintf('Expected "%s", "%s" given.', ResetPasswordRequestInterface::class, $resetPasswordRequest::class));
        }
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
     * @throws UnsupportedUserException
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) { // @phpstan-ignore-line
            throw new UnsupportedUserException(\sprintf('Instance of "%s" is not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->flush();
    }
}
