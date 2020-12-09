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

use App\Entity\CalculationGroup;
use App\Entity\Group;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for calculation group entity.
 *
 * @author Laurent Muller
 *
 * @see \App\Entity\CalculationGroup
 */
class CalculationGroupRepository extends AbstractRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry The connections and entity managers registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CalculationGroup::class);
    }

    /**
     * Count the number of calculations for the given group.
     *
     * @param Group $group the group to search for
     *
     * @return int the number of calculations
     */
    public function countGroupReferences(Group $group): int
    {
        $result = $this->createQueryBuilder('e')
            ->select('DISTINCT COUNT(c.id)')
            ->innerJoin('e.calculation', 'c')
            ->where('e.group = :group')
            ->setParameter('group', $group)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }
}
