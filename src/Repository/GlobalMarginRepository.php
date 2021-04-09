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
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for global margin entity.
 *
 * @method GlobalMargin|null find($id, $lockMode = null, $lockVersion = null)
 * @method GlobalMargin|null findOneBy(array $criteria, array $orderBy = null)
 * @method GlobalMargin[]    findAll()
 * @method GlobalMargin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @author Laurent Muller
 *
 * @see \App\Entity\GlobalMargin
 * @template-extends AbstractRepository<GlobalMargin>
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
        $qb = $this->createQueryBuilder('e')
            ->select('e.margin')
            ->where(':amount >= e.minimum AND :amount < e.maximum')
            ->setParameter('amount', $amount, Types::FLOAT);

        //query
        $query = $qb->getQuery();

        // execute
        return (float) $query->getOneOrNullResult(Query::HYDRATE_SINGLE_SCALAR);
    }
}
