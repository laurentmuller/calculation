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
use Doctrine\DBAL\Types\Types;
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
     * @param string       $alias   the root alias
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function countFiltered(QueryBuilder $builder, string $alias): int
    {
        $field = $this->repository->getSingleIdentifierFieldName();
        $select = "COUNT($alias.$field)";

        return (int) (clone $builder)
            ->select($select)
            ->getQuery()
            ->getSingleScalarResult();
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
     *
     * @psalm-return array<string, \App\Interfaces\SortModeInterface::*>
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
        // default
        $results = parent::handleQuery($query);

        // builder
        $builder = $this->createDefaultQueryBuilder();
        $alias = $builder->getRootAliases()[0];

        // count all
        $results->totalNotFiltered = $results->filtered = $this->count();

        // search clause
        if ($this->search($query, $builder, $alias)) {
            $results->filtered = $this->countFiltered($builder, $alias);
        }

        // order by clause
        $this->orderBy($query, $builder, $alias);

        // offset and limit clause
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
        $this->addSelection($entities, $query);

        // map entities
        $results->rows = $this->mapEntities($entities);

        return $results;
    }

    /**
     * Add the offset and limit clause.
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
     * Add the order by clause.
     *
     * @param DataQuery    $query   the data query
     * @param QueryBuilder $builder the query builder to update
     * @param string       $alias   the root alias
     */
    protected function orderBy(DataQuery $query, QueryBuilder $builder, string $alias): void
    {
        $orderBy = [];

        // add query sort
        if ($sorting = Utils::isString($query->sort) && Utils::isString($query->order)) {
            $this->updateOrderBy($orderBy, $query, $alias);
        }

        // add default column
        if (!$sorting && null !== $column = $this->getDefaultColumn()) {
            $this->updateOrderBy($orderBy, $column, $alias);
        }

        // add default order
        $this->updateOrderBy($orderBy, $this->getDefaultOrder(), $alias);

        // apply
        foreach ($orderBy as $sort => $order) {
            $builder->addOrderBy($sort, $order);
        }
    }

    /**
     * Adds the search clause.
     *
     * @param DataQuery    $query   the data query
     * @param QueryBuilder $builder the query builder to update
     * @param string       $alias   the root alias
     */
    protected function search(DataQuery $query, QueryBuilder $builder, string $alias): bool
    {
        // search?
        $search = $query->search;
        if (!Utils::isString($search)) {
            return false;
        }

        // fields?
        if ([] === $searchFields = $this->getSearchFields()) {
            return false;
        }

        // build
        $whereExpr = new Orx();
        $builderExpr = $builder->expr();
        $repository = $this->repository;
        $likeParameter = ':' . TableInterface::PARAM_SEARCH;
        foreach ($searchFields as $searchField) {
            $fields = (array) $repository->getSearchFields($searchField, $alias);
            foreach ($fields as $field) {
                $whereExpr->add($builderExpr->like($field, $likeParameter));
            }
        }
        if (0 === $whereExpr->count()) {
            return false;
        }

        // update
        $builder->andWhere($whereExpr)
            ->setParameter(TableInterface::PARAM_SEARCH, "%$search%", Types::STRING);

        return true;
    }

    /**
     * Add the missing selected entity.
     *
     * @param AbstractEntity[] $entities the entities to search in or to update
     * @param DataQuery        $query    the query to get values from
     */
    private function addSelection(array &$entities, DataQuery $query): void
    {
        if (0 === $id = $query->id) {
            return;
        }

        // existing?
        foreach ($entities as $entity) {
            if ($id === $entity->getId()) {
                return;
            }
        }

        // find entity
        $entity = $this->repository->find($id);
        if (!$entity instanceof AbstractEntity) {
            return;
        }

        // add to the first position and limit size
        \array_unshift($entities, $entity);
        if (\count($entities) > $query->limit) {
            \array_pop($entities);
        }
    }

    /**
     * @return string[]
     */
    private function getSearchFields(): array
    {
        return \array_map(
            static fn (Column $c): string => $c->getField(),
            \array_filter(
                $this->getColumns(),
                static fn (Column $c): bool => $c->isSearchable()
            )
        );
    }

    /**
     * Update the order by clause.
     *
     * @psalm-param array<string, \App\Interfaces\SortModeInterface::*> $orderBy
     * @psalm-param DataQuery|Column|array<string, \App\Interfaces\SortModeInterface::*> $value
     */
    private function updateOrderBy(array &$orderBy, DataQuery|Column|array $value, string $alias): void
    {
        if ($value instanceof DataQuery) {
            $value = [$value->sort => $value->order];
        } elseif ($value instanceof Column) {
            $value = [$value->getField() => $value->getOrder()];
        }

        foreach ($value as $field => $order) {
            $sortField = $this->repository->getSortField($field, $alias);
            if (!\array_key_exists($sortField, $orderBy)) {
                $orderBy[$sortField] = $order;
            }
        }
    }
}
