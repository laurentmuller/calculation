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
 * @template-extends AbstractRepository<GroupMargin>
 */
class GroupMarginRepository extends AbstractRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry The connections and entity managers registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GroupMargin::class);
    }

    /**
     * Gets the margin, in percent, for the given group and amount.
     *
     * @param Group $group  the group
     * @param float $amount the amount to get percent for
     *
     * @return float the margin, in percent, if found; 0 otherwise
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMargin(Group $group, float $amount): float
    {
        // builder
        $builder = $this->createQueryBuilder('e')
            ->select('e.margin')
            ->where('e.group = :group')
            ->andWhere(':amount >= e.minimum')
            ->andWhere(':amount < e.maximum')
            ->setParameter('group', $group->getId(), Types::INTEGER)
            ->setParameter('amount', $amount, Types::FLOAT);

        // execute
        return (float) $builder->getQuery()->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }
}
