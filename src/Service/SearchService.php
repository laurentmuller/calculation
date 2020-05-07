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

namespace App\Service;

use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Customer;
use App\Entity\Product;
use App\Utils\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Service to search data in all entities.
 *
 * @author Laurent Muller
 */
class SearchService
{
    /**
     * The content column name.
     */
    public const COLUMN_CONTENT = 'content';

    /**
     * The field column name.
     */
    public const COLUMN_FIELD = 'field';

    /**
     * The identifier column name.
     */
    public const COLUMN_ID = 'id';

    /**
     * The type column name.
     */
    public const COLUMN_TYPE = 'type';

    /**
     * Limit value to returns all rows.
     */
    public const NO_LIMIT = -1;

    /**
     * The search parameter name.
     */
    private const SEARCH_PARAM = 'search';

    /**
     * The column names and types.
     *
     * @var string[]
     */
    private static $COLUMNS = [
        self::COLUMN_ID => 'integer',
        self::COLUMN_TYPE => 'string',
        self::COLUMN_FIELD => 'string',
        self::COLUMN_CONTENT => 'string',
    ];

    /**
     * The debug mode.
     *
     * @var bool
     */
    private $debug;

    /**
     * The manager.
     *
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * The result set mapping.
     *
     * @var ResultSetMapping|null
     */
    private $mapping;

    /**
     * The SQL queries.
     *
     * @var string[]|null
     */
    private $queries;

    /**
     * Constructor.
     *
     * @param EntityManagerInterface $manager the manager to query
     * @param KernelInterface        $kernel  the kernel to get debug mode
     */
    public function __construct(EntityManagerInterface $manager, KernelInterface $kernel)
    {
        $this->manager = $manager;
        $this->debug = $kernel->isDebug();
    }

    /**
     * Gets the number of returned rows.
     *
     * @param string $search the term to search
     *
     * @return int the number of rows
     */
    public function count(?string $search): int
    {
        // check value
        if (!Utils::isString($search)) {
            return 0;
        }

        // get result
        $result = $this->getArrayResult($search);

        // count
        return \count($result);
    }

    /**
     * Search data.
     *
     * Do nothing if the search term is empty or if the limit is equal to 0.
     *
     * @param string $search the term to search
     * @param int    $limit  the number of rows to return or -1 for all
     * @param int    $offset the zero based index of the first row to return
     *
     * @return array the array of results for the given search (can be empty)
     */
    public function search(?string $search, int $limit = 25, int $offset = 0): array
    {
        // check values
        if (!Utils::isString($search) || 0 === $limit) {
            return [];
        }

        // all?
        if (self::NO_LIMIT === $limit) {
            $limit = PHP_INT_MAX;
        }

        // sort, limit and offset
        $extra = " LIMIT {$limit} OFFSET {$offset}";

        // return result
        return $this->getArrayResult($search, $extra);
    }

    /**
     * Create the SQL query for the calculation date.
     */
    protected function createCalculationDateQuery(): self
    {
        $class = Calculation::class;
        $field = 'date';
        $content = "date_format(e.{$field}, '%d.%m.%Y')";

        $this->queries[] = $this->createQueryBuilder($class, $field, $content)
            ->getQuery()
            ->getSQL();

        return $this;
    }

    /**
     * Create the SQL query for the calculation groups.
     */
    protected function createCalculationGroupQuery(): self
    {
        $class = Calculation::class;
        $field = 'group';
        $content = 'g.code';

        $this->queries[] = $this->createQueryBuilder($class, $field, $content)
            ->join('e.groups', 'g')
            ->getQuery()
            ->getSQL();

        return $this;
    }

    /**
     * Create the SQL query for the calculation items.
     */
    protected function createCalculationItemQuery(): self
    {
        $class = Calculation::class;
        $field = 'item';
        $content = 'i.description';

        $this->queries[] = $this->createQueryBuilder($class, $field, $content)
            ->join('e.groups', 'g')
            ->join('g.items', 'i')
            ->getQuery()
            ->getSQL();

        return $this;
    }

    /**
     * Create the SQL query for the calculation state.
     */
    protected function createCalculationStateQuery(): self
    {
        $class = Calculation::class;
        $field = 'state';
        $content = 's.code';

        $this->queries[] = $this->createQueryBuilder($class, $field, $content)
            ->join('e.state', 's')
            ->getQuery()
            ->getSQL();

        return $this;
    }

    /**
     * Creates the SQL queries for the given entity.
     *
     * @param string   $class  the entity class
     * @param string[] $fields the entity fields to search in
     */
    protected function createEntityQueries(string $class, array $fields): self
    {
        foreach ($fields as $field) {
            $this->queries[] = $this->createQueryBuilder($class, $field)
                ->getQuery()
                ->getSQL();
        }

        return $this;
    }

    /**
     * Creates a query builder.
     *
     * @param string $class   the entity class
     * @param string $field   the field name
     * @param string $content the field content to search in or null to use the field name
     */
    protected function createQueryBuilder(string $class, string $field, ?string $content = null): QueryBuilder
    {
        $name = Utils::getShortName($class);
        $content = $content ?: "e.{$field}";
        $where = "{$content} LIKE :" . self::SEARCH_PARAM;

        return $this->manager->createQueryBuilder()
            ->select('e.id')
            ->addSelect("'{$name}'")
            ->addSelect("'{$field}'")
            ->addSelect($content)
            ->from($class, 'e')
            ->where($where);
    }

    /**
     * Creates the native query and returns the array result.
     *
     * @param string $search the term to search
     * @param string $extra  a SQL statement to add to the default native SELECT SQL statement
     */
    protected function getArrayResult(string $search, string $extra = ''): array
    {
        // queries:
        $queries = $this->getQueries();

        // SQL
        $sql = \implode(' UNION ', $queries) . $extra;

        // create query
        $query = $this->manager->createNativeQuery($sql, $this->getResultSetMapping());

        // set parameter
        $query->setParameter(self::SEARCH_PARAM, "%{$search}%");

        return $query->getArrayResult();
    }

    /**
     * Gets the SQL queries.
     *
     * @return string[] the SQL queries
     */
    protected function getQueries(): array
    {
        // build?
        if (empty($this->queries)) {
            // entities queries
            $this->createEntityQueries(Calculation::class, ['id', 'customer', 'description', 'overallTotal'])
                ->createEntityQueries(CalculationState::class, ['code', 'description'])
                ->createEntityQueries(Product::class, ['description', 'supplier', 'price'])
                ->createEntityQueries(Category::class, ['code', 'description']);

            // calculation queries
            $this->createCalculationDateQuery()
                ->createCalculationStateQuery()
                ->createCalculationItemQuery();

            // debug queries
            if ($this->debug) {
                $this->createEntityQueries(Customer::class, ['firstName', 'lastName', 'company'])
                    ->createCalculationGroupQuery();
            }

            // update SQL
            $param = ':' . self::SEARCH_PARAM;
            $columns = \array_keys(self::$COLUMNS);
            foreach ($this->queries as &$query) {
                // replace parameter
                $query = \str_replace('?', $param, $query);

                // replace column's names
                foreach ($columns as $index => $name) {
                    $query = \preg_replace("/AS[ ]\\w+[{$index}]/i", "AS {$name}", $query);
                }
            }
        }

        return $this->queries;
    }

    /**
     * Gets the result set mapping.
     */
    protected function getResultSetMapping(): ResultSetMapping
    {
        if (!$this->mapping) {
            $this->mapping = new ResultSetMapping();
            foreach (self::$COLUMNS as $name => $type) {
                $this->mapping->addScalarResult($name, $name, $type);
            }
        }

        return $this->mapping;
    }
}
