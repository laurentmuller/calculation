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

use App\Enums\SortMode;
use App\Interfaces\EntityInterface;
use App\Interfaces\TableInterface;
use App\Repository\AbstractRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\CountWalker;

/**
 * Abstract table for entities.
 *
 * @template TEntity of EntityInterface
 * @template TRepository of AbstractRepository<TEntity>
 *
 * @phpstan-import-type EntityType from Column
 */
abstract class AbstractEntityTable extends AbstractTable
{
    /** The group by part's name of the query. */
    private const string GROUP_BY_PART = 'groupBy';

    /** The join part's name of the query. */
    private const string JOIN_PART = 'join';

    /**
     * @param TRepository $repository
     */
    public function __construct(private readonly AbstractRepository $repository)
    {
    }

    #[\Override]
    public function getEntityClassName(): string
    {
        return $this->repository->getClassName();
    }

    /**
     * Adds the search clause.
     *
     * @param DataQuery    $query   the data query
     * @param QueryBuilder $builder the query builder to update
     * @param string       $alias   the root alias
     *
     * @return bool true if a search clause is added to the query builder
     */
    protected function addSearch(DataQuery $query, QueryBuilder $builder, string $alias): bool
    {
        if (!$query->isSearch()) {
            return false;
        }

        $whereExpr = new Orx();
        $builderExpr = $builder->expr();
        $repository = $this->repository;
        $searchFields = $this->getSearchFields();
        $parameter = ':' . TableInterface::PARAM_SEARCH;
        foreach ($searchFields as $searchField) {
            $fields = (array) $repository->getSearchFields($searchField, $alias);
            foreach ($fields as $field) {
                $whereExpr->add($builderExpr->like($field, $parameter));
            }
        }

        $builder->andWhere($whereExpr)
            ->setParameter(TableInterface::PARAM_SEARCH, '%' . $query->search . '%', Types::STRING);

        return true;
    }

    /**
     * Gets the total number of unfiltered entities.
     */
    protected function count(): int
    {
        return $this->repository->count();
    }

    /**
     * Creates the query builder.
     *
     * @param string $alias the entity alias
     */
    protected function createQueryBuilder(string $alias = AbstractRepository::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->repository->createDefaultQueryBuilder($alias);
    }

    /**
     * @return TRepository
     */
    protected function getRepository(): AbstractRepository
    {
        return $this->repository;
    }

    #[\Override]
    protected function handleQuery(DataQuery $query): DataResults
    {
        $results = parent::handleQuery($query);
        $builder = $this->createQueryBuilder();
        $alias = $builder->getRootAliases()[0];
        $results->totalNotFiltered = $this->count();
        $results->filtered = $results->totalNotFiltered;
        if ($this->addSearch($query, $builder, $alias)) {
            $results->filtered = $this->countFiltered($builder, $alias);
        }

        $this->addOrderBy($query, $builder, $alias);
        $this->addLimit($query, $builder);

        $q = $builder->getQuery();
        if ([] === $builder->getDQLPart(self::JOIN_PART)) {
            $q->setHint(CountWalker::HINT_DISTINCT, false);
        }

        /** @var EntityType[] $entities */
        $entities = $q->getResult();
        $this->addSelection($entities, $query, $alias);
        $results->rows = $this->mapEntities($entities);

        return $results;
    }

    /**
     * Add the offset and limit clause.
     *
     * @param DataQuery    $query   the data query
     * @param QueryBuilder $builder the query builder to update
     */
    private function addLimit(DataQuery $query, QueryBuilder $builder): void
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
    private function addOrderBy(DataQuery $query, QueryBuilder $builder, string $alias): void
    {
        $orderBy = [];
        if ($query->isSort()) {
            $this->updateOrderBy($orderBy, $alias, $query->sort, $query->order);
        }
        $column = $this->getDefaultColumn();
        if ($column instanceof Column) {
            $this->updateOrderBy($orderBy, $alias, $column->getField(), $column->getOrder());
        }
        foreach ($orderBy as $sort => $order) {
            $builder->addOrderBy($sort, $order);
        }
    }

    /**
     * Add the selected entity if any, and it is missing.
     *
     * @param EntityType[] $entities the entities to search in or to update
     * @param DataQuery    $query    the query to get values from
     * @param string       $alias    the entity alias
     */
    private function addSelection(array &$entities, DataQuery $query, string $alias): void
    {
        $id = $query->id;
        if (0 === $id) {
            return;
        }

        if ($this->anyMatch(
            $entities,
            /** @param EntityType $current */
            fn (EntityInterface|array $current): bool => $id === $this->getEntityId($current)
        )) {
            return;
        }

        /** @var EntityType|null $entity */
        $entity = $this->createQueryBuilder($alias)
            ->where($alias . '.id = :id')
            ->setParameter('id', $id, Types::INTEGER)
            ->getQuery()
            ->getOneOrNullResult();
        if (null === $entity) {
            return;
        }

        \array_unshift($entities, $entity);
        if (\count($entities) > $query->limit) {
            \array_pop($entities);
        }
    }

    /**
     * Count the number of filtered entities.
     *
     * @param QueryBuilder $builder the source builder
     * @param string       $alias   the root alias
     */
    private function countFiltered(QueryBuilder $builder, string $alias): int
    {
        $field = $this->repository->getSingleIdentifierFieldName();
        $builder = $this->updateParts(clone $builder, $alias)
            ->select(\sprintf('COUNT(%s.%s)', $alias, $field));

        return (int) $builder->getQuery()->getSingleScalarResult();
    }

    /**
     * Gets the entity identifier.
     *
     * @param EntityType $entity
     */
    private function getEntityId(array|EntityInterface $entity): ?int
    {
        return \is_array($entity) ? $entity['id'] : $entity->getId();
    }

    /**
     * Get the search fields.
     *
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
     * @param array<string, \SortDirection> $orderBy
     */
    private function updateOrderBy(array &$orderBy, string $alias, string $field, SortMode $order): void
    {
        $sortField = $this->repository->getSortField($field, $alias);
        if (!\array_key_exists($sortField, $orderBy)) {
            $orderBy[$sortField] = $order->direction();
        }
    }

    /**
     * Remove the group by and left join parts of the given builder.
     *
     * @param string $alias the root alias
     */
    private function updateParts(QueryBuilder $builder, string $alias): QueryBuilder
    {
        $builder->resetDQLPart(self::GROUP_BY_PART);

        /** @var array<string, ?Join[]> $part */
        $part = $builder->getDQLPart(self::JOIN_PART);
        if (!isset($part[$alias])) {
            return $builder;
        }

        $joins = \array_filter($part[$alias], static fn (Join $join): bool => Join::LEFT_JOIN !== $join->getJoinType());
        if ([] === $joins) {
            return $builder;
        }

        $builder->resetDQLPart(self::JOIN_PART);
        foreach ($joins as $join) {
            $builder->join($join->getJoin(), (string) $join->getAlias());
        }

        return $builder;
    }
}
