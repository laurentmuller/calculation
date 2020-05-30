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

use App\Entity\CalculationGroup;
use App\Entity\Category;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Types\Types;

/**
 * Repository for calculation group entity.
 *
 * @author Laurent Muller
 *
 * @see \App\Entity\CalculationGroup
 */
class CalculationGroupRepository extends BaseRepository
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
     * Count the number of calculations for the given category.
     *
     * @param Category $category the category to search for
     *
     * @return int the number of calculations
     */
    public function countCategoryReferences(Category $category): int
    {
        $result = $this->createQueryBuilder('e')
            ->select('COUNT(DISTINCT(e.calculation))')
            ->innerJoin('e.category', 'c')
            ->where('c.id = :categoryId')
            ->setParameter('categoryId', $category->getId(), Types::INTEGER)
            ->getQuery()
            ->getSingleResult();

        return (int) $result;
    }
}
