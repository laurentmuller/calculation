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

use App\Entity\GroupMargin;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for group margin entity.
 *
 * @method GroupMargin|null find($id, $lockMode = null, $lockVersion = null)
 * @method GroupMargin|null findOneBy(array $criteria, array $orderBy = null)
 * @method GroupMargin[]    findAll()
 * @method GroupMargin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @author Laurent Muller
 *
 * @see \App\Entity\GroupMargin
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
     * Gets the margin, in percent, for the given group identifier and amount.
     *
     * @param int   $id     the group identifier
     * @param float $amount the amount to get percent for
     *
     * @return float the margin, in percent, if found; 0 otherwise
     */
    public function getMargin(int $id, float $amount): float
    {
        // builder
        $qb = $this->createQueryBuilder('e');
        $qb->select('e.margin')
            ->where('e.group = :id AND :amount >= e.minimum AND :amount < e.maximum')
            ->setParameter('id', $id, Types::INTEGER)
            ->setParameter('amount', $amount, Types::FLOAT);

        //query
        $query = $qb->getQuery();

        // execute
        return (float) $query->getOneOrNullResult(Query::HYDRATE_SINGLE_SCALAR);
    }
}
