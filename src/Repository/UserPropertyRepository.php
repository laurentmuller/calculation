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

use App\Entity\UserProperty;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Repository for user's property entity.
 *
 * @template-extends AbstractRepository<UserProperty>
 */
class UserPropertyRepository extends AbstractRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry The connections and entity managers registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserProperty::class);
    }

    /**
     * Gets all properties for the given user.
     *
     * @return UserProperty[] the user's properties
     */
    public function findByUser(UserInterface $user): array
    {
        return $this->findBy(['user' => $user]);
    }

    /**
     * Gets a property for the given user and name.
     */
    public function findOneByUserAndName(UserInterface $user, string $name): ?UserProperty
    {
        return $this->findOneBy(['user' => $user, 'name' => $name]);
    }
}
