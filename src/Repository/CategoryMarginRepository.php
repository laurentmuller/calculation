<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CategoryMargin;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query;

/**
 * Repository for category margin entity.
 *
 * @author Laurent Muller
 *
 * @see \App\Entity\CategoryMargin
 */
class CategoryMarginRepository extends BaseRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry The connections and entity managers registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CategoryMargin::class);
    }

    /**
     * Gets the margin, in percent, for the given category identifier and amount.
     *
     * @param int   $id     the category identifier
     * @param float $amount the amount to get percent for
     *
     * @return float the margin, in percent, if found; 0 otherwise
     */
    public function getMargin(int $id, float $amount): float
    {
        // builder
        $qb = $this->createQueryBuilder('e');
        $qb->select('e.margin')
            ->where('e.category = :id AND :amount >= e.minimum AND :amount < e.maximum')
            ->setParameter('id', $id, Types::INTEGER)
            ->setParameter('amount', $amount, Types::FLOAT);

        //query
        $query = $qb->getQuery();

        // execute
        return (float) $query->getOneOrNullResult(Query::HYDRATE_SINGLE_SCALAR);
    }
}
