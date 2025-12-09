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

use App\Entity\Group;
use App\Entity\GroupMargin;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for group margin entity.
 *
 * @extends AbstractRepository<GroupMargin>
 */
class GroupMarginRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupMargin::class);
    }

    /**
     * Gets the margin, in percent, for the given group or group's identifier and amount.
     *
     * @param Group|int $group  the group or the group's identifier
     * @param float     $amount the amount to get percent for
     *
     * @return float the margin in percent or 0.0 if not found
     */
    public function getGroupMargin(Group|int $group, float $amount): float
    {
        if ($group instanceof Group) {
            $group = $group->getId();
        }
        $builder = $this->createQueryBuilder('e')
            ->select('e.margin')
            ->where('e.group = :group')
            ->andWhere(':amount >= e.minimum')
            ->andWhere(':amount < e.maximum')
            ->setParameter('group', $group, Types::INTEGER)
            ->setParameter('amount', $amount, Types::FLOAT);

        return $builder->getQuery()
            ->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR) ?? 0.0;
    }
}
