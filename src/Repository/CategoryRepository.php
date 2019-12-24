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

use App\Entity\Category;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * Repository for category entity.
 *
 * @author Laurent Muller
 *
 * @see \App\Entity\Category
 */
class CategoryRepository extends BaseRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry The connections and entity managers registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * Gets the the list of categories sorted by code.
     *
     * @return array
     */
    public function getList()
    {
        return $this->getSortedBuilder()
            ->getQuery()
            ->getArrayResult();
    }

//     /**
//      * Gets the the list of categories sorted by code and containing products.
//      *
//      * @return array
//      */
//     public function getNotEmptyList()
//     {
//         return $this->getSortedBuilder()
//             ->innerJoin('c.products', 'p')
//             ->distinct()
//             ->getQuery()
//             ->getArrayResult();
//     }

    /**
     * Gets the query builder for the list of categories sorted by code.
     */
    public function getSortedBuilder(): QueryBuilder
    {
        $field = (string) $this->getSortFields('code');

        return $this->createQueryBuilder(self::DEFAULT_ALIAS)
            ->orderBy($field, Criteria::ASC);
    }
}
