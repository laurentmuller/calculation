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
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for category entity.
 *
 * @author Laurent Muller
 *
 * @see \App\Entity\Category
 */
class CategoryRepository extends AbstractRepository
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
     * @return Category[] the categories
     */
    public function getList(): array
    {
        return $this->getSortedBuilder()
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Gets categories with the number of products.
     *
     * <b>Note:</b> Only categories with at least one product are returned.
     *
     * @return array an array with the category and the number of product
     */
    public function getListCount(): array
    {
        $builder = $this->createQueryBuilder('c')
            ->select('c.id')
            ->addSelect('c.code')
            ->addSelect('c.description')
            ->addSelect('COUNT(p.id) as count')
            ->innerJoin('c.products', 'p')
            ->groupBy('c.id')
            ->orderBy('c.code', Criteria::ASC);

        return $builder->getQuery()->getArrayResult();
    }

    /**
     * Gets the query builder for the list of categories sorted by code.
     *
     * @param string $alias the default entity alias
     */
    public function getSortedBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        $field = (string) $this->getSortFields('code', $alias);

        return $this->createQueryBuilder($alias)
            ->orderBy($field, Criteria::ASC);
    }
}
