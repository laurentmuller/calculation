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
 */
class UserRepository extends AbstractRepository implements ResetPasswordRequestRepositoryInterface, PasswordUpgraderInterface
{
    use ResetPasswordRequestRepositoryTrait;

    /**
     * Constructor.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * {@inheritdoc}
     *
     * @see ResetPasswordRequestRepositoryInterface
     *
     * @throws UnsupportedUserException
     */
    public function createResetPasswordRequest(object $user, \DateTimeInterface $expiresAt, string $selector, string $hashedToken): ResetPasswordRequestInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $user::class));
        }

        return $user->setResetPasswordRequest($expiresAt, $selector, $hashedToken);
    }

    /**
     * Gets all users order by username.
     *
     * @return User[]
     */
    public function findAllByUsername(): array
    {
        return $this->findBy([], ['username' => Criteria::ASC]);
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
     * {@inheritdoc}
     *
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
     * Gets the query builder for the list of users sorted by username.
     *
     * @param string $alias the default entity alias
     */
    public function getSortedBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        $field = $this->getSortField('username', $alias);

        return $this->createQueryBuilder($alias)
            ->orderBy($field, Criteria::ASC);
    }

    /**
     * {@inheritdoc}
     *
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
            ->setParameter('time', $time, Types::DATE_IMMUTABLE)
            ->getQuery();

        return (int) $query->execute();
    }

    /**
     * {@inheritdoc}
     *
     * @see ResetPasswordRequestRepositoryInterface
     *
     * @throws UnsupportedUserException
     */
    public function removeResetPasswordRequest(ResetPasswordRequestInterface $resetPasswordRequest): void
    {
        if (!$resetPasswordRequest instanceof User) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $resetPasswordRequest::class));
        }

        $resetPasswordRequest->eraseResetPasswordRequest();
        $this->flush();
    }

    /**
     * {@inheritdoc}
     *
     * @see PasswordUpgraderInterface
     *
     * @throws UnsupportedUserException
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->flush();
    }
}
