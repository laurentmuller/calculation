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
use App\Entity\UserProperty;
use Doctrine\Persistence\ManagerRegistry;

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
     * Gets a property for the given user and name.
     */
    public function findByName(User $user, string $name): ?UserProperty
    {
        return $this->findOneBy(['user' => $user, 'name' => $name]);
    }

    /**
     * Gets all properties for the given user.
     *
     * @return UserProperty[] the user's properties
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user]);
    }
}
