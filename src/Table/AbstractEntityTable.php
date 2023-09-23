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
use App\Utils\StringUtils;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Join;
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
     * The group by part name of the query.
     */
    private const GROUP_BY_PART = 'groupBy';

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
        $builder = $this->updateJoin(clone $builder)
            ->resetDQLPart(self::GROUP_BY_PART)
            ->select("COUNT($alias.$field)");

        return (int) $builder->getQuery()->getSingleScalarResult();
    }

    /**
     * Creates a default query builder.
     *
     * @param literal-string $alias the entity alias
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
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function handleQuery(DataQuery $query): DataResults
    {
        $results = parent::handleQuery($query);
        $builder = $this->createDefaultQueryBuilder();
        $alias = $builder->getRootAliases()[0];
        $results->totalNotFiltered = $results->filtered = $this->count();
        if ($this->search($query, $builder, $alias)) {
            $results->filtered = $this->countFiltered($builder, $alias);
        }
        $this->orderBy($query, $builder, $alias);
        $this->limit($query, $builder);
        $q = $builder->getQuery();
        if (empty($builder->getDQLPart(self::JOIN_PART))) {
            $q->setHint(CountWalker::HINT_DISTINCT, false);
        }
        /** @var AbstractEntity[]|array<array{id: int}> $entities */
        $entities = $q->getResult();
        $this->addSelection($entities, $query);
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
        $sorting = StringUtils::isString($query->sort);
        if ($sorting && StringUtils::isString($query->order)) {
            $this->updateOrderBy($orderBy, $query, $alias);
        }
        if (!$sorting) {
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
     * Adds the search clause.
     *
     * @param DataQuery    $query   the data query
     * @param QueryBuilder $builder the query builder to update
     * @param string       $alias   the root alias
     */
    protected function search(DataQuery $query, QueryBuilder $builder, string $alias): bool
    {
        $search = $query->search;
        if (!StringUtils::isString($search)) {
            return false;
        }
        $searchFields = $this->getSearchFields();
        if ([] === $searchFields) {
            return false;
        }
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
        $builder->andWhere($whereExpr)
            ->setParameter(TableInterface::PARAM_SEARCH, "%$search%", Types::STRING);

        return true;
    }

    /**
     * Add selected entity if missing.
     *
     * @param AbstractEntity[]|array<array{id: int}> $entities the entities to search in or to update
     * @param DataQuery                              $query    the query to get values from
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function addSelection(array &$entities, DataQuery $query): void
    {
        $id = $query->id;
        if (0 === $id) {
            return;
        }

        foreach ($entities as $entity) {
            if ($id === (\is_array($entity) ? $entity['id'] : $entity->getId())) {
                return;
            }
        }

        /** @psalm-var AbstractEntity|array{id: int}|null $entity */
        $entity = $this->createDefaultQueryBuilder()
            ->where(AbstractRepository::DEFAULT_ALIAS . '.id = :id')
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
     * Remove the left join parts of the given builder.
     */
    private function updateJoin(QueryBuilder $builder): QueryBuilder
    {
        /** @psalm-var array{e: ?Join[]} $part */
        $part = $builder->getDQLPart(self::JOIN_PART);
        if (!isset($part['e'])) {
            return $builder;
        }
        $joins = \array_filter($part['e'], static fn (Join $join): bool => Join::LEFT_JOIN !== $join->getJoinType());
        if ([] === $joins) {
            return $builder;
        }
        $builder->resetDQLPart(self::JOIN_PART);
        foreach ($joins as $join) {
            // @phpstan-ignore-next-line
            $builder->join($join->getJoin(), (string) $join->getAlias());
        }

        return $builder;
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
