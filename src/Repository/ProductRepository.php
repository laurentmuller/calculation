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
use App\Entity\Product;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for product entity.
 *
 * @author Laurent Muller
 *
 * @see \App\Entity\Product
 */
class ProductRepository extends AbstractRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry The connections and entity managers registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Count the number of products for the given category.
     *
     * @param Category $category the category to search for
     *
     * @return int the number of products
     */
    public function countCategoryReferences(Category $category): int
    {
        $result = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->innerJoin('e.category', 'c')
            ->where('c.id = :categoryId')
            ->setParameter('categoryId', $category->getId(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    /**
     * {@inheritdoc}
     */
    public function createDefaultQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        return parent::createDefaultQueryBuilder($alias)
            ->innerJoin("$alias.category", 'c');
    }

    /**
     * Gets all products order by group, category and description.
     *
     * @return Product[]
     */
    public function findAllByGroup(): array
    {
        $builder = $this->createQueryBuilder('p')
            ->innerJoin('p.category', 'c')
            ->innerJoin('c.parent', 'g')
            ->select('p')
            ->orderBy('g.code')
            ->addOrderBy('c.code')
            ->addOrderBy('p.description');

        return $builder->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchFields(string $field, string $alias = self::DEFAULT_ALIAS)
    {
        switch ($field) {
            case 'category.id':
                return 'c.id';
            case 'category.code':
                return 'c.code';
            default:
                return parent::getSearchFields($field, $alias);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSortFields(string $field, string $alias = self::DEFAULT_ALIAS)
    {
        switch ($field) {
            case 'category.id':
            case 'category.code':
                return 'c.code';
            default:
                return parent::getSortFields($field, $alias);
        }
    }

    /**
     * Search for products (used by calculation to add a new item).
     *
     * @param string $value      the search term
     * @param int    $maxResults the maximum number of results to retrieve (the "limit")
     *
     * @return array an array, maybe empty, of matching products
     */
    public function search(string $value, int $maxResults = 15): array
    {
        $builder = $this->createQueryBuilder('p')
            ->select('p.description')
            ->addSelect('p.unit')
            ->addSelect('p.price')
            ->addSelect('c.id as categoryId')
            ->addSelect('upper(c.code) as category_code')
            ->addSelect('upper(r.code) as parent_code')
            ->addSelect("CONCAT(upper(c.code), ' - ', upper(r.code)) AS category")
            ->innerJoin('p.category', 'c')
            ->innerJoin('c.parent', 'r')
            ->orderBy('c.code')
            ->addOrderBy('p.description')
            ->setMaxResults($maxResults);

        // where clause
        $param = ':search';
        $expr = $builder->expr();
        $or = $expr->orx(
                $expr->like('p.description', $param),
                $expr->like('c.code', $param)
        );
        $builder->where($or)
            ->setParameter($param, "%{$value}%", Types::STRING);

        return $builder->getQuery()->getArrayResult();
    }
}
