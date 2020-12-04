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
use Doctrine\ORM\Query;
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
     * The alias for the group entity.
     */
    public const GROUP_ALIAS = 'g';

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
     * Gets the number of child categories for the given parent (group).
     */
    public function countGroupReferences(Category $category): int
    {
        $result = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.parent = :parent')
            ->setParameter('parent', $category)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    /**
     * Gets all categories order by code.
     *
     * @return Category[]
     */
    public function findAllByCode(): array
    {
        return $this->getSortedBuilder()
            ->getQuery()
            ->getResult();
    }

    /**
     * Gets all groups order by code.
     *
     * @return Category[]
     */
    public function findAllGroupsByCode(): array
    {
        return $this->findBy(['parent' => null], ['code' => Criteria::ASC]);
    }

    /**
     * Gets the predicate (clause WHERE) to filter the child categories.
     *
     * @param string $alias the default entity alias
     *
     * @return string the predicate
     */
    public static function getCategoryPredicate(string $alias = self::DEFAULT_ALIAS): string
    {
        return "$alias.parent IS NOT NULL";
    }

    /**
     * Gets the predicate (clause WHERE) to filter the root categories (group).
     *
     * @param string $alias the default entity alias
     *
     * @return string the predicate
     */
    public static function getGroupPredicate(string $alias = self::DEFAULT_ALIAS): string
    {
        return "$alias.parent IS NULL";
    }

    /**
     * Gets the root categories (group) order by code.
     *
     * @return Category[] the root categories
     */
    public function getGroups(): array
    {
        return $this->findBy(['parent' => null], ['code' => Criteria::ASC]);
    }

    /**
     * Creates a search query for parent categories (group).
     *
     * @param array  $sortedFields the sorted fields where key is the field name and value is the sort mode ("ASC" or "DESC")
     * @param string $alias        the entity alias
     *
     * @see AbstractRepository::createDefaultQueryBuilder()
     */
    public function getGroupSearchQuery(array $sortedFields = [], string $alias = self::DEFAULT_ALIAS): Query
    {
        // builder
        $builder = $this->createDefaultQueryBuilder($alias);

        // filter
        $builder->where($this->getGroupPredicate($alias));

        // order by clause
        if (!empty($sortedFields)) {
            foreach ($sortedFields as $name => $order) {
                $fields = (array) $this->getSortFields($name, $alias);
                foreach ($fields as $field) {
                    $builder->addOrderBy($field, $order);
                }
            }
        }

        // query
        return $builder->getQuery();
    }

    /**
     * Gets the query builder for the list of parent categories (group) sorted by code.
     *
     * @param string $alias the default entity alias
     */
    public function getGroupSortedBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        $field = (string) $this->getSortFields('code', $alias);
        $predicate = self::getGroupPredicate($alias);

        return $this->createQueryBuilder($alias)
            ->orderBy($field, Criteria::ASC)
            ->where($predicate);
    }

    /**
     * Gets the the list of categories sorted by code.
     *
     * @return array an array with the category identifier ('id) and the category code ('code')
     */
    public function getList(): array
    {
        $builder = $this->createQueryBuilder('e')
            ->select('e.id')
            ->addSelect('e.code')
            ->addSelect('r.code as parent_code')
            ->addSelect("CONCAT(e.code, ' - ', r.code) AS full_code")
            ->innerJoin('e.parent', 'r')
            ->orderBy('e.code', Criteria::ASC);

        return $builder->getQuery()
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
     * {@inheritdoc}
     */
    public function getSearchFields(string $field, string $alias = self::DEFAULT_ALIAS)
    {
        switch ($field) {
            case 'parent.code':
                return self::GROUP_ALIAS . '.code';
            default:
                return parent::getSearchFields($field, $alias);
        }
    }

    /**
     * Gets the query builder for the list of categories sorted by code.
     *
     * @param string $alias the default entity alias
     */
    public function getSortedBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        $field = (string) $this->getSortFields('code', $alias);
        $predicate = self::getCategoryPredicate($alias);

        return $this->createQueryBuilder($alias)
            ->orderBy($field, Criteria::ASC)
            ->where($predicate);
    }

    /**
     * {@inheritdoc}
     */
    public function getSortFields(string $field, string $alias = self::DEFAULT_ALIAS)
    {
        switch ($field) {
            case 'parent.code':
                return self::GROUP_ALIAS . '.code';
            default:
                return parent::getSortFields($field, $alias);
        }
    }
}
