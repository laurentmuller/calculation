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

namespace App\BootstrapTable;

use App\Entity\AbstractEntity;
use App\Repository\AbstractRepository;
use App\Util\Utils;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Query\Expr\Orx;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Abstract table for entities.
 *
 * @author Laurent Muller
 */
abstract class AbstractBootstrapEntityTable extends AbstractBootstrapTable
{
    /**
     * The respository.
     */
    protected AbstractRepository $repository;

    /**
     * Constructor.
     */
    public function __construct(SerializerInterface $serializer, AbstractRepository $repository)
    {
        parent::__construct($serializer);
        $this->repository = $repository;
    }

    /**
     * Add the limit and the maximum result to return.
     *
     * @param Request      $request the request
     * @param QueryBuilder $builder the query builder to update
     *
     * @return int[] the offset and the limit parameters
     */
    public function addLimit(Request $request, QueryBuilder $builder): array
    {
        $offset = (int) $request->get(self::PARAM_OFFSET, 0);
        $limit = (int) $this->getRequestValue($request, self::PARAM_LIMIT, self::PAGE_SIZE);
        $builder->setFirstResult($offset)
            ->setMaxResults($limit);

        return [$offset, $limit];
    }

    /**
     * Update the given query builder by adding the order by clause.
     *
     * @param Request      $request the request
     * @param QueryBuilder $builder the query builder to update
     *
     * @return string[] the sort field and order parameters
     */
    public function addOrderBy(Request $request, QueryBuilder $builder): array
    {
        $orderBy = [];
        $repository = $this->repository;
        $sort = (string) $this->getRequestValue($request, self::PARAM_SORT, '');
        $order = (string) $this->getRequestValue($request, self::PARAM_ORDER, Criteria::ASC);

        if (Utils::isString($sort)) {
            $fields = (array) $repository->getSortFields($sort);
            foreach ($fields as $field) {
                if (!\array_key_exists($field, $orderBy)) {
                    $orderBy[$field] = $order;
                }
            }
        }

        // default column
        if (!Utils::isString($sort) && $column = $this->getDefaultColumn()) {
            $order = $column->getOrder();
            $sort = $column->getField();
            $fields = (array) $repository->getSortFields($sort);
            foreach ($fields as $field) {
                if (!\array_key_exists($field, $orderBy)) {
                    $orderBy[$field] = $order;
                }
            }
        }

        // default order
        $defaultSort = $this->getDefaultOrder();
        foreach ($defaultSort as $defaultField => $defaultOrder) {
            $fields = (array) $repository->getSortFields($defaultField);
            foreach ($fields as $field) {
                if (!\array_key_exists($field, $orderBy)) {
                    $orderBy[$field] = $defaultOrder;
                }
            }
            // default sort field
            if (!Utils::isString($sort)) {
                $sort = $defaultField;
            }
        }

        // apply sort
        foreach ($orderBy as $key => $value) {
            $builder->addOrderBy($key, $value);
        }

        return [$sort, \strtolower($order)];
    }

    /**
     * Adds the search clause, if applicable.
     *
     * @param Request      $request the request
     * @param QueryBuilder $builder the query builder to update
     *
     * @return string the seeach parameter
     */
    public function addSearch(Request $request, QueryBuilder $builder): string
    {
        $search = (string) $request->get(self::PARAM_SEARCH, '');
        if (Utils::isString($search)) {
            $expr = new Orx();
            $columns = $this->getColumns();
            $repository = $this->repository;
            foreach ($columns as $column) {
                if ($column->isSearchable()) {
                    $fields = (array) $repository->getSearchFields($column->getField());
                    foreach ($fields as $field) {
                        $expr->add($field . ' LIKE :' . self::PARAM_SEARCH);
                    }
                }
            }
            if ($expr->count()) {
                $builder->andWhere($expr)
                    ->setParameter(self::PARAM_SEARCH, "%{$search}%");
            }
        }

        return $search;
    }

    /**
     * Gets the total number of unfiltered entities.
     */
    public function count(): int
    {
        return $this->repository->count([]);
    }

    /**
     * Count the number of filtered entities.
     *
     * @param QueryBuilder $builder the source builder
     */
    public function countFiltered(QueryBuilder $builder): int
    {
        $alias = $builder->getRootAliases()[0];
        $field = $this->repository->getSingleIdentifierFieldName();
        $select = "COUNT($alias.$field)";

        $cloned = (clone $builder);
        $cloned->select($select);

        return (int) $cloned->getQuery()->getSingleScalarResult();
    }

    /**
     * Creates a default query builder.
     *
     * @param string $alias the entity alias
     */
    public function createDefaultQueryBuilder(string $alias = AbstractRepository::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->repository->createDefaultQueryBuilder($alias);
    }

    /**
     * Gets the entity class name.
     */
    public function getEntityClassName(): string
    {
        return $this->repository->getClassName();
    }

    /**
     * Gets the repository.
     */
    public function getRepository(): AbstractRepository
    {
        return $this->repository;
    }

    /**
     * Maps the given entities.
     *
     * @param AbstractEntity[] $entities the entities to map
     *
     * @return array the mapped entities
     */
    public function mapEntities(array $entities): array
    {
        $columns = $this->getColumns();
        $accessor = PropertyAccess::createPropertyAccessor();

        return \array_map(function (AbstractEntity $entity) use ($columns, $accessor) {
            return $this->mapValues($entity, $columns, $accessor);
        }, $entities);
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
}
