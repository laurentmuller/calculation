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

use App\Entity\GlobalMargin;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for global margin entity.
 *
 * @extends AbstractRepository<GlobalMargin>
 */
class GlobalMarginRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GlobalMargin::class);
    }

    /**
     * Gets all global margins ordered by the minimum.
     *
     * @return GlobalMargin[]
     */
    public function findByMinimum(): array
    {
        return $this->findBy([], ['minimum' => self::SORT_ASC]);
    }

    /**
     * Gets the margin in percent for the given amount.
     *
     * @param float $amount the amount to get percent for
     *
     * @return float the margin in percent, if found; 0 otherwise
     */
    public function getMargin(float $amount): float
    {
        $query = $this->createQueryBuilder('e')
            ->select('e.margin')
            ->where(':amount >= e.minimum')
            ->andWhere(':amount < e.maximum')
            ->setParameter('amount', $amount, Types::FLOAT)
            ->getQuery();

        return $query->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR) ?? 0.0;
    }
}
