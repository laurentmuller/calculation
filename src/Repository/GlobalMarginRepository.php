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

use App\Entity\GlobalMargin;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for global margin entity.
 *
 * @template-extends AbstractRepository<GlobalMargin>
 *
 * @author Laurent Muller
 */
class GlobalMarginRepository extends AbstractRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry The connections and entity managers registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GlobalMargin::class);
    }

    /**
     * Gets all global margins order by minimum.
     *
     * @return GlobalMargin[]
     */
    public function findAllByMinimum(): array
    {
        return $this->findBy([], ['minimum' => Criteria::ASC]);
    }

    /**
     * Gets the margin, in percent, for the given amount.
     *
     * @param float $amount the amount to get percent for
     *
     * @return float the margin, in percent, if found; 0 otherwise
     */
    public function getMargin(float $amount): float
    {
        // builder
        $builder = $this->createQueryBuilder('e')
            ->select('e.margin')
            ->where(':amount >= e.minimum')
            ->andWhere(':amount < e.maximum')
            ->setParameter('amount', $amount, Types::FLOAT);

        // execute
        return (float) $builder->getQuery()->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);
    }
}
