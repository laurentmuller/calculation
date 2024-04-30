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

use App\Interfaces\EntityInterface;
use App\Interfaces\TableInterface;
use App\Repository\AbstractRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Exception\ORMException;
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
 * @psalm-import-type EntityType from Column
 */
abstract class AbstractEntityTable extends AbstractTable
{
    /**
     * The group by part name of the query.
     */
    private const GROUP_BY_PART = 'groupBy';

    /**
     * The join part name of the query.
     */
    private const JOIN_PART = 'join';

    /**
     * @psalm-param TRepository $repository
     */
    public function __construct(private readonly AbstractRepository $repository)
    {
    }

    public function getEntityClassName(): ?string
    {
        return $this->repository->getClassName();
    }

    /**
     * Adds the search clause.
     *
     * @param DataQuery      $query   the data query
     * @param QueryBuilder   $builder the query builder to update
     * @param literal-string $alias   the root alias
     *
     * @return bool true if a search clause is added to the query builder
     */
    protected function addSearch(DataQuery $query, QueryBuilder $builder, string $alias): bool
    {
        $search = $query->search;
        if ('' === $search) {
            return false;
        }
        $searchFields = $this->getSearchFields();
        if ([] === $searchFields) {
            return false;
        }

        $whereExpr = new Orx();
        $builderExpr = $builder->expr();
        $repository = $this->repository;
        $parameter = ':' . TableInterface::PARAM_SEARCH;
        foreach ($searchFields as $searchField) {
            $fields = (array) $repository->getSearchFields($searchField, $alias);
            foreach ($fields as $field) {
                $whereExpr->add($builderExpr->like($field, $parameter));
            }
        }
        if (0 === $whereExpr->count()) {
            return false;
        }

        $builder->andWhere($whereExpr)
            ->setParameter(TableInterface::PARAM_SEARCH, "%$search%", Types::STRING);

        return true;
    }

    /**
     * Gets the total number of unfiltered entities.
     */
    protected function count(): int
    {
        return $this->repository->count([]);
    }

    /**
     * Creates the query builder.
     *
     * @param literal-string $alias the entity alias
     */
    protected function createQueryBuilder(string $alias = AbstractRepository::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->repository->createDefaultQueryBuilder($alias);
    }

    /**
     * Gets the default sort order.
     *
     * @return array<string, string> an array where each key is the field name, and the value is the order
     *                               direction ('asc' or 'desc')
     *
     * @psalm-return array<string, self::SORT_*>
     */
    protected function getDefaultOrder(): array
    {
        return [];
    }

    /**
     * @psalm-return TRepository
     */
    protected function getRepository(): AbstractRepository
    {
        return $this->repository;
    }

    /**
     * @throws ORMException
     */
    protected function handleQuery(DataQuery $query): DataResults
    {
        $results = parent::handleQuery($query);
        $builder = $this->createQueryBuilder();
        /** @psalm-var literal-string $alias */
        $alias = $builder->getRootAliases()[0];

        $results->totalNotFiltered = $results->filtered = $this->count();
        if ($this->addSearch($query, $builder, $alias)) {
            $results->filtered = $this->countFiltered($builder, $alias);
        }

        $this->addOrderBy($query, $builder, $alias);
        $this->addLimit($query, $builder);

        $q = $builder->getQuery();
        if ([] === $builder->getDQLPart(self::JOIN_PART)) {
            $q->setHint(CountWalker::HINT_DISTINCT, false);
        }

        /** @psalm-var EntityType[] $entities */
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
     * Add the clause order by.
     *
     * @param DataQuery      $query   the data query
     * @param QueryBuilder   $builder the query builder to update
     * @param literal-string $alias   the root alias
     */
    private function addOrderBy(DataQuery $query, QueryBuilder $builder, string $alias): void
    {
        $orderBy = [];
        if ('' !== $query->sort) {
            $this->updateOrderBy($orderBy, $query, $alias);
        } else {
            $column = $this->getDefaultColumn();
            if ($column instanceof Column) {
                $this->updateOrderBy($orderBy, $column, $alias);
            }
        }
        $this->updateOrderBy($orderBy, $this->getDefaultOrder(), $alias);

        foreach ($orderBy as $sort => $order) {
            $builder->addOrderBy($sort, $order);
        }
    }

    /**
     * Add the selected entity if any and if it is missing.
     *
     * @param EntityType[]   $entities the entities to search in or to update
     * @param DataQuery      $query    the query to get values from
     * @param literal-string $alias    the entity alias
     *
     * @throws ORMException
     */
    private function addSelection(array &$entities, DataQuery $query, string $alias): void
    {
        $id = $query->id;
        if (0 === $id) {
            return;
        }

        foreach ($entities as $entity) {
            if ($id === $this->getEntityId($entity)) {
                return;
            }
        }

        /** @psalm-var EntityType|null $entity */
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
     * @param QueryBuilder   $builder the source builder
     * @param literal-string $alias   the root alias
     *
     * @throws ORMException
     */
    private function countFiltered(QueryBuilder $builder, string $alias): int
    {
        $field = $this->repository->getSingleIdentifierFieldName();
        $builder = $this->updateParts(clone $builder, $alias)
            ->select("COUNT($alias.$field)");

        return (int) $builder->getQuery()->getSingleScalarResult();
    }

    /**
     * Gets the entity identifier.
     *
     * @psalm-param EntityType $entity
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
        $mapCallback = static fn (Column $c): string => $c->getField();
        $filterCallback = static fn (Column $c): bool => $c->isSearchable();

        return \array_map($mapCallback, \array_filter($this->getColumns(), $filterCallback));
    }

    /**
     * Update the clause order by.
     *
     * @psalm-param array<string, string> $orderBy
     * @psalm-param DataQuery|Column|array<string, string> $value
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

    /**
     * Remove the group by and left join parts of the given builder.
     *
     * @param literal-string $alias the root alias
     */
    private function updateParts(QueryBuilder $builder, string $alias): QueryBuilder
    {
        $builder->resetDQLPart(self::GROUP_BY_PART);

        /** @psalm-var array<string, ?Join[]> $part */
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
