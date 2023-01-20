<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Table;

use App\Entity\AbstractEntity;
use App\Interfaces\TableInterface;
use App\Repository\AbstractRepository;
use App\Util\Utils;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\CountWalker;

/**
 * Abstract table for entities.
 *
 * @template T of AbstractEntity
 */
abstract class AbstractEntityTable extends AbstractTable
{
    /**
     * The join part name of the query.
     */
    private const JOIN_PART = 'join';

    /**
     * The where part name of the query builder.
     */
    private const WHERE_PART = 'where';

    /**
     * Constructor.
     *
     * @param AbstractRepository<T> $repository
     */
    public function __construct(protected readonly AbstractRepository $repository)
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
     * @return AbstractRepository<T> $repository
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
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function countFiltered(QueryBuilder $builder): int
    {
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
     *
     * @psalm-param literal-string $alias
     */
    protected function createDefaultQueryBuilder(string $alias = AbstractRepository::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->repository->createDefaultQueryBuilder($alias);
    }

    /**
     * Gets the default sort order.
     *
     * @return array<string, string> an array where each key is the field name and the value is the order direction ('asc' or 'desc')
     */
    protected function getDefaultOrder(): array
    {
        return [];
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function handleQuery(DataQuery $query): DataResults
    {
        $results = parent::handleQuery($query);

        // builder
        $builder = $this->createDefaultQueryBuilder();

        // count all
        $results->totalNotFiltered = $results->filtered = $this->count();

        // add search clause
        $this->search($query, $builder);

        // count filtered
        if (!empty($builder->getDQLPart(self::WHERE_PART))) {
            $results->filtered = $this->countFiltered($builder);
        }

        // add order by clause
        $this->orderBy($query, $builder);

        // add offset and limit
        $this->limit($query, $builder);

        // join?
        $q = $builder->getQuery();
        if (empty($builder->getDQLPart(self::JOIN_PART))) {
            $q->setHint(CountWalker::HINT_DISTINCT, false);
        }

        // get result
        /** @var AbstractEntity[] $entities */
        $entities = $q->getResult();

        // add selection
        if (0 !== $query->id) {
            $this->addSelection($entities, $query->id, $query->limit);
        }

        // map entities
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
        $orderBy = [];

        // add query sort
        if ($sorting = Utils::isString($query->sort) && Utils::isString($query->order)) {
            $this->updateOrderBy($orderBy, $query->sort, $query->order);
        }

        // add default column
        if (!$sorting && null !== $column = $this->getDefaultColumn()) {
            $this->updateOrderBy($orderBy, $column->getField(), $column->getOrder());
        }

        // add default order
        $defaultOrder = $this->getDefaultOrder();
        foreach ($defaultOrder as $field => $order) {
            $this->updateOrderBy($orderBy, $field, $order);
        }

        // apply
        foreach ($orderBy as $sort => $order) {
            $builder->addOrderBy($sort, $order);
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
        if (!Utils::isString($search)) {
            return;
        }

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
        if ($expr->count() > 0) {
            $builder->andWhere($expr)
                ->setParameter(TableInterface::PARAM_SEARCH, "%$search%");
        }
    }

    /**
     * Add the missing selected entity (if applicable).
     *
     * @param AbstractEntity[] $entities the entities to search in or to update
     * @param int              $id       the entity identifier to search for or to add
     * @param int              $limit    the maximum number of allowed entities (limit)
     */
    private function addSelection(array &$entities, int $id, int $limit): void
    {
        // existing?
        foreach ($entities as $entity) {
            if ($id === $entity->getId()) {
                return;
            }
        }

        // get entity
        $entity = $this->repository->find($id);
        if ($entity instanceof AbstractEntity) {
            // add to the first position
            \array_unshift($entities, $entity);

            // limit size
            if (\count($entities) > $limit) {
                \array_pop($entities);
            }
        }
    }

    /**
     * Update the order by clause. Do nothing if the given field already exist in the array.
     *
     * @param array<string, string> $orderBy the order by clause to update
     * @param string                $field   the sort field
     * @param string                $order   the sort direction
     */
    private function updateOrderBy(array &$orderBy, string $field, string $order): void
    {
        $sortField = $this->repository->getSortField($field);
        if (!\array_key_exists($sortField, $orderBy)) {
            $orderBy[$sortField] = $order;
        }
    }
}
