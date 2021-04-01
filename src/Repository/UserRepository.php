<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Persistence\Repository\ResetPasswordRequestRepositoryTrait;
use SymfonyCasts\Bundle\ResetPassword\Persistence\ResetPasswordRequestRepositoryInterface;

/**
 * Repository for user entity.
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @author Laurent Muller
 *
 * @see \App\Entity\User
 */
class UserRepository extends AbstractRepository implements ResetPasswordRequestRepositoryInterface
{
    use ResetPasswordRequestRepositoryTrait;

    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry The connections and entity managers registry
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
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        return $user->setResetPasswordRequest($expiresAt, $selector, $hashedToken);
    }

    /**
     * Gets all users order by user name.
     *
     * @return User[]
     */
    public function findAllByUsername(): array
    {
        return $this->findBy([], ['username' => Criteria::ASC]);
    }

    /**
     * Finds a user by its email.
     *
     * @param string $email the email to search for
     *
     * @return User|null the user instance or null if the user can not be found
     */
    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Find a user by its user name.
     *
     * @param string $username the user name to search for
     *
     * @return User|null the user instance or null if the user can not be found
     */
    public function findByUsername(string $username): ?User
    {
        return $this->findOneBy(['username' => $username]);
    }

    /**
     * Finds a user by its user name or email.
     *
     * @param string $usernameOrEmail the user name or the email to search for
     *
     * @return User|null the user instance or null if the user can not be found
     */
    public function findByUsernameOrEmail(string $usernameOrEmail): ?User
    {
        if (\preg_match('/^.+\@\S+\.\S+$/', $usernameOrEmail)) {
            $user = $this->findByEmail($usernameOrEmail);
            if (null !== $user) {
                return $user;
            }
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
     * Gets the query builder for the list of users sorted by user name.
     *
     * @param string $alias the default entity alias
     */
    public function getSortedBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        $field = (string) $this->getSortFields('username', $alias);

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
            ->setParameter('time', $time)
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
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', \get_class($resetPasswordRequest)));
        }

        $resetPasswordRequest->eraseResetPasswordRequest();
        $this->_em->flush();
    }

    /**
     * Update the date of last login for the given user.
     *
     * @param User $user the user to update
     *
     * @return bool this function returns always true
     */
    public function updateLastLogin(User $user): bool
    {
        $user->setLastLogin(new \DateTimeImmutable());
        $this->_em->flush();

        return true;
    }
}
