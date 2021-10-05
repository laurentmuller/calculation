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

namespace App\Service;

use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Customer;
use App\Entity\Group;
use App\Entity\Product;
use App\Entity\Task;
use App\Traits\CheckerTrait;
use App\Util\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Service to search data in all entities.
 *
 * @author Laurent Muller
 */
class SearchService
{
    use CheckerTrait;

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
     * The column names and types.
     *
     * @var string[]
     */
    private const COLUMNS = [
        self::COLUMN_ID => 'integer',
        self::COLUMN_TYPE => 'string',
        self::COLUMN_FIELD => 'string',
        self::COLUMN_CONTENT => 'string',
    ];

    /**
     * The search parameter name.
     */
    private const SEARCH_PARAM = 'search';

    /**
     * The debug mode.
     */
    private bool $debug;

    /**
     * The manager.
     */
    private EntityManagerInterface $manager;

    /**
     * The result set mapping.
     */
    private ?ResultSetMapping $mapping = null;

    /**
     * The SQL queries.
     *
     * @var string[]
     */
    private ?array $queries = null;

    /**
     * Constructor.
     */
    public function __construct(EntityManagerInterface $manager, AuthorizationCheckerInterface $checker, bool $isDebug)
    {
        $this->manager = $manager;
        $this->checker = $checker;
        $this->debug = $isDebug;
    }

    /**
     * Gets the number of returned rows.
     *
     * @param string $search the term to search
     * @param string $entity the entity to search in or null for all
     *
     * @return int the number of rows
     */
    public function count(?string $search, ?string $entity = null): int
    {
        // check value
        if (!Utils::isString($search)) {
            return 0;
        }

        // get result
        $result = $this->getArrayResult($search, $entity);

        // count
        return \count($result);
    }

    /**
     * Gets the entities class and name.
     *
     * @return string[]
     */
    public function getEntities(): array
    {
        $entities = [
            $this->getEntityName(Calculation::class) => 'calculation.name',
            $this->getEntityName(Product::class) => 'product.name',
            $this->getEntityName(Task::class) => 'task.name',
            $this->getEntityName(Category::class) => 'category.name',
            $this->getEntityName(Group::class) => 'group.name',
            $this->getEntityName(CalculationState::class) => 'calculationstate.name',
        ];
        if ($this->debug) {
            $entities[$this->getEntityName(Customer::class)] = 'customer.name';
        }

        return $entities;
    }

    /**
     * Search data.
     *
     * Do nothing if the search term is empty or if the limit is equal to 0.
     *
     * @param string $search the term to search
     * @param string $entity the entity name to search in or null for all
     * @param int    $limit  the number of rows to return or -1 for all
     * @param int    $offset the zero based index of the first row to return
     *
     * @return array the array of results for the given search (can be empty)
     */
    public function search(?string $search, ?string $entity = null, int $limit = 25, int $offset = 0): array
    {
        // check values
        if (!Utils::isString($search) || 0 === $limit) {
            return [];
        }

        // all?
        if (self::NO_LIMIT === $limit) {
            $limit = \PHP_INT_MAX;
        }

        // sort, limit and offset
        $extra = " LIMIT {$limit} OFFSET {$offset}";

        // return result
        return $this->getArrayResult($search, $entity, $extra);
    }

    /**
     * Create the SQL query for the calculation dates.
     */
    private function createCalculationDatesQuery(): self
    {
        $class = Calculation::class;
        if ($this->isGrantedSearch($class)) {
            $fields = ['date', 'createdAt', 'updatedAt'];
            foreach ($fields as $field) {
                $content = "date_format(e.{$field}, '%d.%m.%Y')";
                $key = $this->getKey($class, $field);
                $this->queries[$key] = $this->createQueryBuilder($class, $field, $content)
                    ->getQuery()
                    ->getSQL();
            }
        }

        return $this;
    }

    /**
     * Create the SQL query for the calculation groups.
     */
    private function createCalculationGroupQuery(): self
    {
        $class = Calculation::class;
        if ($this->isGrantedSearch($class)) {
            $field = 'group';
            $content = 'g.code';
            $key = $this->getKey($class, $field);

            $this->queries[$key] = $this->createQueryBuilder($class, $field, $content)
                ->join('e.groups', 'g')
                ->getQuery()
                ->getSQL();
        }

        return $this;
    }

    /**
     * Create the SQL query for the calculation items.
     */
    private function createCalculationItemQuery(): self
    {
        $class = Calculation::class;
        if ($this->isGrantedSearch($class)) {
            $field = 'item';
            $content = 'i.description';
            $key = $this->getKey($class, $field);

            $this->queries[$key] = $this->createQueryBuilder($class, $field, $content)
                ->join('e.groups', 'g')
                ->join('g.categories', 'c')
                ->join('c.items', 'i')
                ->getQuery()
                ->getSQL();
        }

        return $this;
    }

    /**
     * Create the SQL query for the calculation state.
     */
    private function createCalculationStateQuery(): self
    {
        $class = Calculation::class;
        if ($this->isGrantedSearch($class)) {
            $field = 'state';
            $content = 's.code';
            $key = $this->getKey($class, $field);

            $this->queries[$key] = $this->createQueryBuilder($class, $field, $content)
                ->join('e.state', 's')
                ->getQuery()
                ->getSQL();
        }

        return $this;
    }

    /**
     * Creates the SQL queries for the given entity.
     *
     * @param string   $class  the entity class
     * @param string[] $fields the entity fields to search in
     */
    private function createEntityQueries(string $class, array $fields): self
    {
        // granted?
        if ($this->isGrantedSearch($class)) {
            foreach ($fields as $field) {
                $key = $this->getKey($class, $field);
                $this->queries[$key] = $this->createQueryBuilder($class, $field)
                    ->getQuery()
                    ->getSQL();
            }
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
    private function createQueryBuilder(string $class, string $field, ?string $content = null): QueryBuilder
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
     * @param string $entity the entity to search in or null for all
     * @param string $extra  a SQL statement to add to the default native SELECT SQL statement
     */
    private function getArrayResult(string $search, ?string $entity = null, string $extra = ''): array
    {
        // queries:
        $queries = $this->getQueries();

        // entity?
        if (Utils::isString($entity)) {
            $queries = \array_filter($queries, function (string $key) use ($entity): bool {
                return 0 === \stripos($key, $entity);
            }, \ARRAY_FILTER_USE_KEY);
        }

        // empty?
        if (empty($queries)) {
            return [];
        }

        // SQL
        $sql = \implode(' UNION ', $queries) . $extra;

        // create query
        $query = $this->manager->createNativeQuery($sql, $this->getResultSetMapping());

        // set parameter
        $query->setParameter(self::SEARCH_PARAM, "%{$search}%");

        return $query->getArrayResult();
    }

    /**
     * Gets the entity name for the given class.
     *
     * @param string $class the entity class
     *
     * @return string the entity name
     */
    private function getEntityName(string $class): string
    {
        return \strtolower(Utils::getShortName($class));
    }

    /**
     * Gets the query key for the given class name and field.
     *
     * @param string $class the class name
     * @param string $field the field
     *
     * @return string the key
     */
    private function getKey(string $class, string $field): string
    {
        $shortName = \strtolower(Utils::getShortName($class));

        return "$shortName.$field";
    }

    /**
     * Gets the SQL queries.
     *
     * @return string[] the SQL queries
     */
    private function getQueries(): array
    {
        // created?
        if (empty($this->queries)) {
            // entities queries
            $this->createEntityQueries(Calculation::class, ['id', 'customer', 'description', 'overallTotal', 'createdBy', 'updatedBy'])
                ->createEntityQueries(CalculationState::class, ['code', 'description'])
                ->createEntityQueries(Product::class, ['description', 'supplier', 'price'])
                ->createEntityQueries(Task::class, ['name'])
                ->createEntityQueries(Category::class, ['code', 'description'])
                ->createEntityQueries(Group::class, ['code', 'description']);

            // custom calculation queries
            $this->createCalculationDatesQuery()
                ->createCalculationStateQuery()
                ->createCalculationItemQuery();

            // debug queries
            if ($this->debug) {
                $this->createEntityQueries(Customer::class, ['firstName', 'lastName', 'company', 'address', 'zipCode', 'city'])
                    ->createCalculationGroupQuery();
            }

            // update SQL
            $param = ':' . self::SEARCH_PARAM;
            $columns = \array_keys(self::COLUMNS);
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
    private function getResultSetMapping(): ResultSetMapping
    {
        if (null === $this->mapping) {
            $this->mapping = new ResultSetMapping();
            foreach (self::COLUMNS as $name => $type) {
                $this->mapping->addScalarResult($name, $name, $type);
            }
        }

        return $this->mapping;
    }

    /**
     * Returns if the given subject can be listed and displayed.
     *
     * @param string $class the subject (entity name)
     *
     * @return bool true if the subject can be listed and displayed
     */
    private function isGrantedSearch(string $class): bool
    {
        return $this->isGrantedList($class) && $this->isGrantedShow($class);
    }
}
