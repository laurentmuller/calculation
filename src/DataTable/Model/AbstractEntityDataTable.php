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

namespace App\DataTable\Model;

use App\Entity\AbstractEntity;
use App\Repository\AbstractRepository;
use App\Service\ApplicationService;
use App\Util\Utils;
use DataTables\Column;
use DataTables\DataTableQuery;
use DataTables\DataTableResults;
use DataTables\DataTablesInterface;
use DataTables\Order;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Abstract data table handler for entities.
 *
 * @author Laurent Muller
 */
abstract class AbstractEntityDataTable extends AbstractDataTable
{
    /**
     * The name of key for the definition of search fields.
     */
    private const KEY_SEARCH = 'search';

    /**
     * the name of key for the definition of sort fields.
     */
    private const KEY_SORT = 'sort';

    /**
     * The name of the search parameter.
     */
    private const SEARCH_PARAMETER = 'search';

    /**
     * The repository to get entities.
     *
     * @var AbstractRepository
     */
    protected $repository;

    /**
     * Constructor.
     *
     * @param ApplicationService  $application the application to get parameters
     * @param SessionInterface    $session     the session to save/retrieve user parameters
     * @param DataTablesInterface $datatables  the datatables to handle request
     * @param AbstractRepository  $repository  the repository to get entities
     */
    public function __construct(ApplicationService $application, SessionInterface $session, DataTablesInterface $datatables, AbstractRepository $repository)
    {
        parent::__construct($application, $session, $datatables);
        $this->repository = $repository;
    }

    /**
     * Count entities of the given query builder.
     *
     * @param QueryBuilder $builder the query builder
     * @param string       $prefix  the entity prefix
     *
     * @return int the number of entities
     */
    protected function count(QueryBuilder $builder, string $prefix = AbstractRepository::DEFAULT_ALIAS): int
    {
        $builder->select("COUNT($prefix.id)");

        return (int) $builder->getQuery()->getSingleScalarResult();
    }

    /**
     * Gets the total number of entities.
     *
     * @return int the number of entities
     */
    protected function countAll(): int
    {
        return $this->count($this->createQueryBuilder());
    }

    /**
     * Gets filtered entities count.
     *
     * @param QueryBuilder $source the original query
     *
     * @return int the number of filtered entities
     */
    protected function countFiltered(QueryBuilder $source): int
    {
        return $this->count(clone $source);
    }

    /**
     * {@inheritdoc}
     */
    protected function createDataTableResults(DataTableQuery $query): DataTableResults
    {
        /** @var Column[] $columns */
        $columns = $query->columns;

        // map columns
        $definitions = [];
        foreach ($columns as $column) {
            $name = $column->name;
            $definitions[$name][self::KEY_SORT] = (array) $this->repository->getSortFields($name);
            $definitions[$name][self::KEY_SEARCH] = (array) $this->repository->getSearchFields($name);
        }

        // result and query
        $results = new DataTableResults();
        $builder = $this->createQueryBuilder();

        // total count
        $results->recordsTotal = $this->countAll();

        // columns search
        $this->createSearchColumns($builder, $columns, $definitions);

        // global search
        $this->createSearchGlobal($builder, $columns, $definitions, $query->search->value);

        // filtered count.
        $results->recordsFiltered = $this->countFiltered($builder);

        // order by
        $this->createOrderBy($builder, $query->order, $columns, $definitions);

        // offset and limit.
        $builder->setFirstResult($query->start);
        if (self::SHOW_ALL !== $query->length) {
            $builder->setMaxResults($query->length);
        }

        // get items
        $items = $builder->getQuery()->getResult();

        // transform
        $results->data = \array_map([$this, 'toArray'], $items);

        return $results;
    }

    /**
     * Creates the order by clause.
     *
     * @param QueryBuilder $builder     the query builder
     * @param Order[]      $orders      the request orders
     * @param Column[]     $columns     the datatable columns
     * @param array        $definitions the database definitions
     */
    protected function createOrderBy(QueryBuilder $builder, array $orders, array $columns, array $definitions): self
    {
        // default order
        $defaultOrder = $this->getDefaultOrder();

        // add orders
        foreach ($orders as $order) {
            $index = $order->column;
            $column = $columns[$index];
            if ($column->orderable) {
                $name = $column->name;
                $direction = $order->dir;
                $fields = $definitions[$name][self::KEY_SORT];
                foreach ($fields as $field) {
                    $builder->addOrderBy($field, $direction);
                }

                // remove
                unset($defaultOrder[$name]);
            }
        }

        // add remaining default orders
        foreach ($defaultOrder as $name => $direction) {
            $fields = $definitions[$name][self::KEY_SORT];
            foreach ($fields as $field) {
                $builder->addOrderBy($field, $direction);
            }
        }

        return $this;
    }

    /**
     * Creates the query builder.
     *
     * @param string $alias the entity alias
     *
     * @return QueryBuilder the query builder
     */
    protected function createQueryBuilder($alias = AbstractRepository::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->repository->createDefaultQueryBuilder($alias);
    }

    /**
     * Update the given query builder by adding the columns search (if any).
     *
     * @param QueryBuilder $builder     the query builder to update
     * @param Column[]     $columns     the datatable columns
     * @param array        $definitions the database definitions
     */
    protected function createSearchColumns(QueryBuilder $builder, array $columns, array $definitions): self
    {
        foreach ($columns as $column) {
            if ($column->searchable && $column->search->value) {
                $name = $column->name;
                $value = $column->search->value;
                $parameter = \str_replace('.', '_', $name);
                $fields = $definitions[$name][self::KEY_SEARCH];
                foreach ($fields as $field) {
                    if ($expression = $this->createSearchExpression($field, $parameter)) {
                        $builder->andWhere($expression)->setParameter($parameter, "%{$value}%");
                    }
                }

                // remove the searchable from global search
                $column->searchable = false;
            }
        }

        return $this;
    }

    /**
     * Create a search expression.
     *
     * @param string $field     the field name to search in
     * @param string $parameter the search parameter name
     *
     * @return Expr\Comparison|string|null the search expression or null if not applicable
     */
    protected function createSearchExpression(string $field, string $parameter)
    {
        return "{$field} LIKE :{$parameter}";
    }

    /**
     * Update the given query builder by adding the global search expression (if any).
     *
     * @param QueryBuilder $builder     the query builder to update
     * @param Column[]     $columns     the datatable columns
     * @param array        $definitions the database definitions
     * @param string       $search      the search term (if any)
     */
    protected function createSearchGlobal(QueryBuilder $builder, array $columns, array $definitions, ?string $search): self
    {
        if ($search) {
            $expr = new Expr\Orx();
            foreach ($columns as $column) {
                if ($column->searchable) {
                    $name = $column->name;
                    $fields = $definitions[$name][self::KEY_SEARCH];
                    foreach ($fields as $field) {
                        if ($expression = $this->createSearchExpression($field, self::SEARCH_PARAMETER)) {
                            $expr->add($expression);
                        }
                    }
                }
            }
            if (0 !== $expr->count()) {
                $builder->andWhere($expr)
                    ->setParameter(self::SEARCH_PARAMETER, "%{$search}%");
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function createSessionPrefix(): string
    {
        $className = $this->repository->getClassName();

        return Utils::getShortName($className);
    }

    /**
     * Gets the default order to apply.
     *
     * Each order is apply, if not yet present, after the request order.
     *
     * @return array an array where each key is the column name and the value is the order direction ('asc' or 'desc')
     */
    protected function getDefaultOrder(): array
    {
        return [];
    }

    /**
     * Converts the given entity to an array.
     *
     * The default implementation use the <code>getCellValues</code> function.
     *
     * @param AbstractEntity $item the entity to convert
     *
     * @see AbstractDataTable::getCellValues()
     */
    protected function toArray($item): array
    {
        return $this->getCellValues($item);
    }
}
