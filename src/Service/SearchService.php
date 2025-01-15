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

namespace App\Service;

use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Customer;
use App\Entity\Group;
use App\Entity\Product;
use App\Entity\Task;
use App\Interfaces\EntityInterface;
use App\Interfaces\TimestampableInterface;
use App\Traits\AuthorizationCheckerAwareTrait;
use App\Utils\FormatUtils;
use App\Utils\StringUtils;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Service to search data in all entities.
 *
 * @psalm-type SearchType = array{
 *     id: int,
 *     type: string,
 *     field: string,
 *     content: string,
 *     entityName: string,
 *     fieldName: string
 * }
 */
class SearchService implements ServiceSubscriberInterface
{
    use AuthorizationCheckerAwareTrait;
    use ServiceMethodsSubscriberTrait;

    /**
     * The action column name.
     */
    final public const COLUMN_ACTION = 'action';

    /**
     * The content column name.
     */
    final public const COLUMN_CONTENT = 'content';

    /**
     * The entity column name.
     */
    final public const COLUMN_ENTITY_NAME = 'entityName';

    /**
     * The field column name.
     */
    final public const COLUMN_FIELD = 'field';

    /**
     * The field column name.
     */
    final public const COLUMN_FIELD_NAME = 'fieldName';

    /**
     * The delete granted column name.
     */
    final public const COLUMN_GRANTED_DELETE = 'allowDelete';

    /**
     * The edit granted column name.
     */
    final public const COLUMN_GRANTED_EDIT = 'allowEdit';

    /**
     * The show granted column name.
     */
    final public const COLUMN_GRANTED_SHOW = 'allowShow';

    /**
     * The identifier column name.
     */
    final public const COLUMN_ID = 'id';

    /**
     * The type column name.
     */
    final public const COLUMN_TYPE = 'type';

    /**
     * Limit value to return all rows.
     */
    final public const NO_LIMIT = -1;

    /**
     * The column names and types.
     *
     * @var array<string,string>
     */
    private const COLUMNS = [
        self::COLUMN_ID => Types::INTEGER,
        self::COLUMN_TYPE => Types::STRING,
        self::COLUMN_FIELD => Types::STRING,
        self::COLUMN_CONTENT => Types::STRING,
    ];

    /**
     * The search parameter name.
     */
    private const SEARCH_PARAM = 'search';

    public function __construct(
        private readonly EntityManagerInterface $manager,
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
        #[Target('calculation.search')]
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * Gets the number of returned rows.
     *
     * @param ?string $search the term to search
     * @param ?string $entity the entity to search in or null for all
     *
     * @return int the number of rows
     *
     * @psalm-return non-negative-int
     */
    public function count(?string $search, ?string $entity = null): int
    {
        if (!StringUtils::isString($search)) {
            return 0;
        }
        $result = $this->getArrayResult($search, $entity);

        return \count($result);
    }

    /**
     * Format the given content, depending on the given field.
     */
    public function formatContent(string $field, mixed $content): string
    {
        return match ($field) {
            'Calculation.id' => FormatUtils::formatId((int) $content),
            'Calculation.overallTotal',
            'Product.price' => FormatUtils::formatAmount((float) $content),
            default => (string) $content
        };
    }

    /**
     * Gets entity classes and names.
     *
     * @return array<string, string>
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
     * @param ?string $search the term to search
     * @param ?string $entity the entity name to search in or null for all
     * @param int     $limit  the number of rows to return or -1 for all
     * @param int     $offset the zero-based-index of the first row to return
     *
     * @return array the array of results for the given search (can be empty)
     *
     * @psalm-return SearchType[]
     */
    public function search(?string $search, ?string $entity = null, int $limit = 25, int $offset = 0): array
    {
        if (!StringUtils::isString($search) || 0 === $limit) {
            return [];
        }
        if (self::NO_LIMIT === $limit) {
            $limit = \PHP_INT_MAX;
        }
        $extra = \sprintf(' LIMIT %d OFFSET %d', $limit, $offset);

        return $this->getArrayResult($search, $entity, $extra);
    }

    /**
     * @param array<string, string> $queries
     */
    private function addQuery(array &$queries, string $key, QueryBuilder $builder): void
    {
        $sql = $builder->getQuery()->getSQL();
        if (\is_string($sql)) {
            $queries[$key] = $sql;
        }
    }

    /**
     * Create the SQL query for the calculation dates.
     *
     * @param array<string, string> $queries
     */
    private function createCalculationDatesQuery(array &$queries): void
    {
        $class = Calculation::class;
        if (!$this->isGrantedSearch($class)) {
            return;
        }
        $fields = ['date', 'createdAt', 'updatedAt'];
        foreach ($fields as $field) {
            $content = "date_format(e.$field, '%d.%m.%Y')";
            $key = $this->getKey($class, $field);
            $builder = $this->createQueryBuilder($class, $field, $content);
            $this->addQuery($queries, $key, $builder);
        }
    }

    /**
     * Create the SQL query for the calculation groups.
     *
     * @param array<string, string> $queries
     */
    private function createCalculationGroupQuery(array &$queries): void
    {
        $class = Calculation::class;
        if (!$this->isGrantedSearch($class)) {
            return;
        }
        $field = 'group';
        $content = 'g.code';
        $key = $this->getKey($class, $field);
        $builder = $this->createQueryBuilder($class, $field, $content)
            ->join('e.groups', 'g');
        $this->addQuery($queries, $key, $builder);
    }

    /**
     * Create the SQL query for the calculation items.
     *
     * @param array<string, string> $queries
     */
    private function createCalculationItemQuery(array &$queries): void
    {
        $class = Calculation::class;
        if (!$this->isGrantedSearch($class)) {
            return;
        }
        $field = 'item';
        $content = 'i.description';
        $key = $this->getKey($class, $field);
        $builder = $this->createQueryBuilder($class, $field, $content)
            ->join('e.groups', 'g')
            ->join('g.categories', 'c')
            ->join('c.items', 'i');
        $this->addQuery($queries, $key, $builder);
    }

    /**
     * Create the SQL query for the calculation state.
     *
     * @param array<string, string> $queries
     */
    private function createCalculationStateQuery(array &$queries): void
    {
        $class = Calculation::class;
        if (!$this->isGrantedSearch($class)) {
            return;
        }
        $field = 'state';
        $content = 's.code';
        $key = $this->getKey($class, $field);
        $builder = $this->createQueryBuilder($class, $field, $content)
            ->join('e.state', 's');
        $this->addQuery($queries, $key, $builder);
    }

    /**
     * Creates the SQL queries for the given entity.
     *
     * @template TEntity of EntityInterface
     *
     * @param array<string, string> $queries
     * @param class-string<TEntity> $class     the entity class
     * @param string                ...$fields the entity fields to search in
     */
    private function createEntityQueries(array &$queries, string $class, string ...$fields): void
    {
        if (!$this->isGrantedSearch($class)) {
            return;
        }
        if ($this->isTimestampable($class)) {
            $fields = \array_unique(\array_merge($fields, ['createdBy', 'updatedBy']));
        }
        foreach ($fields as $field) {
            $key = $this->getKey($class, $field);
            $builder = $this->createQueryBuilder($class, $field);
            $this->addQuery($queries, $key, $builder);
        }
    }

    private function createNativeQuery(string $sql): NativeQuery
    {
        return $this->manager->createNativeQuery($sql, $this->getResultSetMapping());
    }

    /**
     * Creates a query builder.
     *
     * @template TEntity of EntityInterface
     *
     * @param class-string $class   the entity class
     * @param string       $field   the field name
     * @param ?string      $content the field content to search in or null to use the field name
     *
     * @psalm-param class-string<TEntity> $class
     */
    private function createQueryBuilder(string $class, string $field, ?string $content = null): QueryBuilder
    {
        $alias = 'e';
        $name = StringUtils::getShortName($class);
        $content ??= "$alias.$field";
        $where = \sprintf('%s LIKE :%s', $content, self::SEARCH_PARAM);

        return $this->manager->createQueryBuilder()
            ->select("$alias.id")
            ->addSelect("'$name'")
            ->addSelect("'$field'")
            ->addSelect($content)
            ->from($class, $alias)
            ->where($where);
    }

    /**
     * Creates the native query and returns the array result.
     *
     * @param string  $search the term to search
     * @param ?string $entity the entity to search in or null for all
     * @param string  $extra  the SQL statement to add to the default native SELECT SQL statement
     *
     * @psalm-return SearchType[]
     */
    private function getArrayResult(string $search, ?string $entity = null, string $extra = ''): array
    {
        $queries = $this->getQueries();
        if (StringUtils::isString($entity)) {
            $queries = \array_filter(
                $queries,
                static fn (string $key): bool => 0 === \stripos($key, $entity),
                \ARRAY_FILTER_USE_KEY
            );
        }
        if ([] === $queries) {
            return [];
        }

        $sql = \implode(' UNION ', $queries) . $extra;
        $query = $this->createNativeQuery($sql);
        $query->setParameter(self::SEARCH_PARAM, "%$search%", Types::STRING);

        /** @psalm-var SearchType[] */
        return $query->getArrayResult();
    }

    /**
     * Gets the entity name for the given class.
     *
     * @param string $class the entity class
     *
     * @return string the entity name
     *
     * @psalm-param class-string $class
     */
    private function getEntityName(string $class): string
    {
        return \strtolower(StringUtils::getShortName($class));
    }

    /**
     * Gets the query key for the given class name and field.
     *
     * @param string $class the class name
     * @param string $field the field
     *
     * @return string the key
     *
     * @psalm-param class-string $class
     */
    private function getKey(string $class, string $field): string
    {
        return \sprintf('%s.%s', $this->getEntityName($class), $field);
    }

    /**
     * Gets the SQL queries.
     *
     * @return array<string, string> the SQL queries
     */
    private function getQueries(): array
    {
        return $this->cache->get('queries', function () {
            $queries = [];
            $this->createEntityQueries($queries, Calculation::class, 'id', 'customer', 'description', 'overallTotal');
            $this->createEntityQueries($queries, CalculationState::class, 'code', 'description');
            $this->createEntityQueries($queries, Product::class, 'description', 'supplier', 'price');
            $this->createEntityQueries($queries, Task::class, 'name');
            $this->createEntityQueries($queries, Category::class, 'code', 'description');
            $this->createEntityQueries($queries, Group::class, 'code', 'description');
            $this->createCalculationDatesQuery($queries);
            $this->createCalculationStateQuery($queries);
            $this->createCalculationItemQuery($queries);
            if ($this->debug) {
                $this->createEntityQueries(
                    $queries,
                    Customer::class,
                    'firstName',
                    'lastName',
                    'company',
                    'address',
                    'zipCode',
                    'city'
                );
                $this->createCalculationGroupQuery($queries);
            }
            $this->updateSQL($queries);

            return $queries;
        });
    }

    /**
     * Gets the result set mapping.
     */
    private function getResultSetMapping(): ResultSetMapping
    {
        return $this->cache->get('mapping', function (): ResultSetMapping {
            $mapping = new ResultSetMapping();
            foreach (self::COLUMNS as $name => $type) {
                $mapping->addScalarResult($name, $name, $type);
            }

            return $mapping;
        });
    }

    /**
     * Returns if the given subject (entity class name) can be listed and displayed.
     */
    private function isGrantedSearch(string $subject): bool
    {
        return $this->isGrantedList($subject) && $this->isGrantedShow($subject);
    }

    private function isTimestampable(string $class): bool
    {
        return \is_a($class, TimestampableInterface::class, true);
    }

    /**
     * Update the SQL content of queries.
     *
     * @param array<string, string> $queries
     */
    private function updateSQL(array &$queries): void
    {
        $values = [];
        $columns = \array_keys(self::COLUMNS);
        foreach ($columns as $index => $name) {
            $values["/AS \\w+[$index]/i"] = "AS $name";
        }
        $param = ':' . self::SEARCH_PARAM;
        foreach ($queries as &$query) {
            $query = \str_replace('?', $param, $query);
            $query = StringUtils::pregReplaceAll($values, $query);
        }
    }
}
