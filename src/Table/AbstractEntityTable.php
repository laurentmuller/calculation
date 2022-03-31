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

namespace App\Table;

use App\Interfaces\TableInterface;
use App\Repository\AbstractRepository;
use App\Util\Utils;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;

/**
 * Abstract table for entities.
 *
 * @author Laurent Muller
 *
 * @template T of \App\Entity\AbstractEntity
 */
abstract class AbstractEntityTable extends AbstractTable
{
    /**
     * The where part name of the query builder.
     */
    private const WHERE_PART = 'where';

    /**
     * Constructor.
     *
     * @psalm-param AbstractRepository<T> $repository
     */
    public function __construct(protected AbstractRepository $repository)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClassName(): ?string
    {
        return $this->repository->getClassName();
    }

    /**
     * Gets the repository.
     *
     * @psalm-return AbstractRepository<T> $repository
     */
    public function getRepository(): AbstractRepository
    {
        return $this->repository;
    }

    /**
     * Gets the total number of unfiltered entities.
     */
    protected function count(): int
    {
        return $this->repository->count([]);
    }

    /**
     * Count the number of filtered entities.
     *
     * @param QueryBuilder $builder the source builder
     */
    protected function countFiltered(QueryBuilder $builder): int
    {
        // clone
        $alias = $builder->getRootAliases()[0];
        $field = $this->repository->getSingleIdentifierFieldName();
        $select = "COUNT($alias.$field)";
        $cloned = (clone $builder)->select($select);

        return (int) $cloned->getQuery()->getSingleScalarResult();
    }

    /**
     * Creates a default query builder.
     *
     * @param string $alias the entity alias
     */
    protected function createDefaultQueryBuilder(string $alias = AbstractRepository::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->repository->createDefaultQueryBuilder($alias);
    }

    /**
     * {@inheritDoc}
     */
    protected function handleQuery(DataQuery $query): DataResults
    {
        $results = parent::handleQuery($query);

        // builder
        $builder = $this->createDefaultQueryBuilder();

        // count all
        $results->totalNotFiltered = $results->filtered = $this->count();

        // search
        $this->search($query, $builder);

        // count filtered
        if (!empty($builder->getDQLPart(self::WHERE_PART))) {
            $results->filtered = $this->countFiltered($builder);
        }

        // sort
        $this->orderBy($query, $builder);

        // limit
        $this->limit($query, $builder);

        // get result and map entities
        /** @var array<\App\Entity\AbstractEntity> $entities */
        $entities = $builder->getQuery()->getResult();
        $results->rows = $this->mapEntities($entities);

        return $results;
    }

    /**
     * Sets the offset and the maximum results to return.
     *
     * @param DataQuery    $query   the data query
     * @param QueryBuilder $builder the query builder to update
     */
    protected function limit(DataQuery $query, QueryBuilder $builder): void
    {
        $builder->setFirstResult($query->offset)
            ->setMaxResults($query->limit);
    }

    /**
     * Update the given query builder by adding the order by clause.
     *
     * @param DataQuery    $query   the data query
     * @param QueryBuilder $builder the query builder to update
     */
    protected function orderBy(DataQuery $query, QueryBuilder $builder): void
    {
        /** @var array<string, string> $orderBy */
        $orderBy = [];
        $sort = $query->sort;
        $order = $query->order;
        if (Utils::isString($sort) && Utils::isString($order)) {
            $this->updateOrderBy($orderBy, $sort, $order);
        }

        // default column
        if (!Utils::isString($sort) && $column = $this->getDefaultColumn()) {
            $sort = $column->getField();
            $order = $column->getOrder();
            $this->updateOrderBy($orderBy, $sort, $order);
        }

        // default order
        $defaultSort = $this->getDefaultOrder();
        foreach ($defaultSort as $defaultField => $defaultOrder) {
            $this->updateOrderBy($orderBy, $defaultField, $defaultOrder);
            if (!Utils::isString($sort)) {
                $sort = $defaultField;
            }
            if (!Utils::isString($order)) {
                $order = $defaultOrder;
            }
        }

        // apply sort
        foreach ($orderBy as $key => $value) {
            $builder->addOrderBy($key, $value);
        }
    }

    /**
     * Adds the search clause, if applicable.
     *
     * @param DataQuery    $query   the data query
     * @param QueryBuilder $builder the query builder to update
     */
    protected function search(DataQuery $query, QueryBuilder $builder): void
    {
        $search = $query->search;
        if (Utils::isString($search)) {
            $expr = new Orx();
            $columns = $this->getColumns();
            $repository = $this->repository;
            foreach ($columns as $column) {
                if ($column->isSearchable()) {
                    $fields = (array) $repository->getSearchFields($column->getField());
                    foreach ($fields as $field) {
                        $expr->add($field . ' LIKE :' . TableInterface::PARAM_SEARCH);
                    }
                }
            }
            if (0 !== $expr->count()) {
                $builder->andWhere($expr)
                    ->setParameter(TableInterface::PARAM_SEARCH, "%$search%");
            }
        }
    }

    /**
     * Update the order by clause.
     *
     * @param array<string, string> $orderBy    the order by clause to update
     * @param string                $orderField the order field to add
     * @param string                $orderSort  the order direction to add
     */
    private function updateOrderBy(array &$orderBy, string $orderField, string $orderSort): void
    {
        $field = $this->repository->getSortField($orderField);
        if (!\array_key_exists($field, $orderBy)) {
            $orderBy[$field] = $orderSort;
        }
    }
}
