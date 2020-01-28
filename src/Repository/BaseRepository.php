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

use App\Doctrine\ColumnHydrator;
use App\Utils\Utils;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

/**
 * Base repository.
 *
 * @author Laurent Muller
 */
abstract class BaseRepository extends ServiceEntityRepository
{
    /**
     * The default entity alias used to create query builder (value = 'e') .
     */
    public const DEFAULT_ALIAS = 'e';

    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry  the connections and entity managers registry
     * @param string          $className the class name of the entity this repository manages
     */
    protected function __construct(ManagerRegistry $registry, string $className)
    {
        parent::__construct($registry, $className);
    }

    /**
     * Creates a default query builder.
     *
     * @param string $alias the entity alias
     */
    public function createDefaultQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->createQueryBuilder($alias);
    }

    /**
     * Gets sorted, distinct and not null values of the given column.
     *
     * @param string $field the field name to get values for
     * @param string $value a value to search within the column or <code>null</code> for all
     * @param int    $limit the maximum number of results to retrieve (the "limit") or <code>-1</code> for all
     *
     * @return array an array, maybe empty; of matching values
     */
    public function getDistinctValues(string $field, ?string $value = null, int $limit = -1): array
    {
        // name
        $name = self::DEFAULT_ALIAS . '.' . $field;

        // select and order
        $builder = $this->createQueryBuilder(self::DEFAULT_ALIAS)
            ->select($name)
            ->distinct()
            ->orderBy($name);

        // search
        $expr = $builder->expr();
        if (Utils::isString($value)) {
            $param = 'search';
            $like = $expr->like($name, ':' . $param);
            $builder->where($like)
                ->setParameter($param, "%{$value}%");
        } else {
            $builder->where($expr->isNotNull($name));
        }

        // limit
        if ($limit > 0) {
            $builder->setMaxResults($limit);
        }

        return $builder->getQuery()
            ->getResult(ColumnHydrator::NAME);
    }

    /**
     * Gets the database search fields.
     *
     * The default implementation returns the alias and the field separated by a dot ('.') character.
     *
     * @param string $field the field name
     * @param string $alias the default entity alias
     *
     * @return string|string[] one on more database search fields
     */
    public function getSearchFields(string $field, string $alias = self::DEFAULT_ALIAS)
    {
        return "$alias.$field";
    }

    /**
     * Creates a search query.
     *
     * @param array $sortedFields the sorted fields where key is the field name and value is the sort mode ("ASC" or "DESC")
     *
     * @see BaseRepository::createDefaultQueryBuilder()
     */
    public function getSearchQuery(array $sortedFields = []): Query
    {
        // builder
        $builder = $this->createDefaultQueryBuilder();

        // order by clause
        if (!empty($sortedFields)) {
            foreach ($sortedFields as $name => $order) {
                $fields = (array) $this->getSortFields($name);
                foreach ($fields as $field) {
                    $builder->addOrderBy($field, $order);
                }
            }
        }

        // query
        return $builder->getQuery();
    }

    /**
     * Gets the database sort fields.
     *
     * The default implementation returns the alias and the field separated by a dot ('.') character.
     *
     * @param string $field the field name
     * @param string $alias the default entity alias
     *
     * @return string|string[] one on more database sort fields
     */
    public function getSortFields(string $field, string $alias = self::DEFAULT_ALIAS)
    {
        return "$alias.$field";
    }
}
