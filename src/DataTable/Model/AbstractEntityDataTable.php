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

namespace App\DataTable\Model;

use App\Repository\AbstractRepository;
use App\Security\EntityVoter;
use App\Util\Utils;
use DataTables\Column;
use DataTables\DataTableQuery;
use DataTables\DataTableResults;
use DataTables\DataTablesInterface;
use DataTables\Order;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

/**
 * Abstract data table handler for entities.
 *
 * @author Laurent Muller
 *
 * @template T of \App\Entity\AbstractEntity
 */
abstract class AbstractEntityDataTable extends AbstractDataTable
{
    /**
     * The name of the global search parameter.
     */
    private const SEARCH_PARAMETER = 'search';

    /**
     * The Twig environment.
     */
    protected ?Environment $environment;

    /**
     * The repository to get entities.
     *
     * @psalm-var AbstractRepository<T> $repository
     */
    protected AbstractRepository $repository;

    /**
     * Constructor.
     *
     * @psalm-param AbstractRepository<T> $repository
     */
    public function __construct(RequestStack $requestStack, DataTablesInterface $datatables, AbstractRepository $repository, Environment $environment = null)
    {
        parent::__construct($requestStack, $datatables);
        $this->repository = $repository;
        $this->environment = $environment;
    }

    /**
     * Format the actions column.
     */
    public function formatActions(int $id): string
    {
        return $this->renderTemplate('macros/_datatables_actions.html.twig', ['id' => $id]);
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
     * {@inheritdoc}
     */
    protected function createDataTableResults(DataTableQuery $query): DataTableResults
    {
        /** @var DataDefinitition[] $definitions */
        $definitions = \array_map(function (Column $column): DataDefinitition {
            $name = $column->name;
            $sortField = $this->repository->getSortField($name);
            $searchFields = $this->repository->getSearchFields($name);

            return new DataDefinitition($column, $sortField, $searchFields);
        }, $query->columns);

        // result and builder
        $results = new DataTableResults();
        $builder = $this->createQueryBuilder();

        // total count
        $results->recordsTotal = $this->count($this->createQueryBuilder());

        // columns search
        $this->createSearchColumns($builder, $definitions);

        // global search
        $this->createSearchGlobal($builder, $definitions, $query->search->value);

        // filtered count
        $results->recordsFiltered = $this->count(clone $builder);

        // order by
        $this->createOrderBy($builder, $definitions, $query->order);

        // offset and limit.
        $builder->setFirstResult(\max($query->start, 0));
        if ($query->length > 0) {
            $builder->setMaxResults($query->length);
        }

        // get items
        $items = $builder->getQuery()->getResult();

        // transform
        $results->data = \array_map(function ($data): array {
            return $this->getCellValues($data);
        }, $items);

        return $results;
    }

    /**
     * Update the given query builder by adding the order by clause.
     *
     * @param QueryBuilder       $builder     the query builder to update
     * @param DataDefinitition[] $definitions the data column definitions
     * @param Order[]            $orders      the columns ordering (zero-based column index and direction)
     */
    protected function createOrderBy(QueryBuilder $builder, array $definitions, array $orders): void
    {
        // default order
        $defaultOrder = $this->getDefaultOrder();

        // add orders
        foreach ($orders as $order) {
            $definition = $definitions[$order->column];
            if ($definition->isOrderable()) {
                $direction = $order->dir;
                $builder->addOrderBy($definition->getSortField(), $direction);

                // remove
                unset($defaultOrder[$definition->getName()]);
            }
        }

        // add remaining default orders
        foreach ($defaultOrder as $name => $direction) {
            if (null !== ($definition = $this->findDefinition($definitions, $name))) {
                $builder->addOrderBy($definition->getSortField(), $direction);
            }
        }
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
     * @param QueryBuilder       $builder     the query builder to update
     * @param DataDefinitition[] $definitions the data column definitions
     */
    protected function createSearchColumns(QueryBuilder $builder, array &$definitions): void
    {
        foreach ($definitions as &$definition) {
            if ($definition->isSearch()) {
                $name = $definition->getName();
                $value = $definition->getSearchValue();
                $parameter = \str_replace('.', '_', $name);
                foreach ($definition->getSearchFields() as $field) {
                    if ($expression = $this->createSearchExpression($field, $parameter)) {
                        $parameterValue = $this->createSearchParameterValue($field, $value);
                        $builder->andWhere($expression)->setParameter($parameter, $parameterValue);
                    }
                }

                // remove the searchable from global search
                $definition->setSearchable(false);
            }
        }
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
     * @param QueryBuilder       $builder     the query builder to update
     * @param DataDefinitition[] $definitions the data column definitions
     * @param string             $search      the search term (if any)
     */
    protected function createSearchGlobal(QueryBuilder $builder, array $definitions, ?string $search): void
    {
        if (Utils::isString($search)) {
            $expr = new Expr\Orx();
            foreach ($definitions as $definition) {
                if ($definition->isSearchable()) {
                    foreach ($definition->getSearchFields() as $field) {
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
    }

    /**
     * Creates the search parameter value.
     *
     * @param string $field the field name to search in
     * @param string $value the search value
     *
     * @return mixed the parameter value
     *
     * @psalm-suppress UnusedParam
     */
    protected function createSearchParameterValue(string $field, string $value)
    {
        return "%{$value}%";
    }

    /**
     * {@inheritdoc}
     */
    protected function createSessionPrefix(): string
    {
        $className = $this->repository->getClassName();

        return EntityVoter::getEntityName($className);
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
     * Render the given template name.
     *
     * @param string $template the template name to render
     * @param array  $context  the template context (parameters)
     *
     * @return string the rendered template, an empty string ('') if this Twig environment is not set
     */
    protected function renderTemplate(string $template, array $context = []): string
    {
        if (null !== $this->environment) {
            return $this->environment->render($template, $context);
        }

        return '';
    }

    /**
     * Finds a data definition for the given column name.
     *
     * @param DataDefinitition[] $definitions the definitions to search in
     * @param string             $name        the column name to search for
     *
     * @return DataDefinitition|null the definition, if found; null otherwise
     */
    private function findDefinition(array $definitions, string $name): ?DataDefinitition
    {
        foreach ($definitions as $definition) {
            if ($name === $definition->getName()) {
                return $definition;
            }
        }

        return null;
    }
}
