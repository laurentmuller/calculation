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
use App\Traits\CheckerAwareTrait;
use App\Util\FormatUtils;
use App\Util\Utils;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

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
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SearchService implements ServiceSubscriberInterface
{
    use CheckerAwareTrait;
    use ServiceSubscriberTrait;

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
     * The result set mapping.
     */
    private ?ResultSetMapping $mapping = null;

    /**
     * The SQL queries.
     *
     * @var array<string, string>
     */
    private array $queries = [];

    /**
     * Constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $manager,
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug
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
     * @throws \ReflectionException
     */
    public function count(?string $search, ?string $entity = null): int
    {
        // check value
        if (!Utils::isString($search)) {
            return 0;
        }

        // get result
        $result = $this->getArrayResult((string) $search, $entity);

        // count
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
     * Gets the entities class and name.
     *
     * @return array<string, string>
     *
     * @throws \ReflectionException
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
     * @param int     $offset the zero based index of the first row to return
     *
     * @return array the array of results for the given search (can be empty)
     *
     * @psalm-return SearchType[]
     *
     * @throws \ReflectionException
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
        $extra = " LIMIT $limit OFFSET $offset";

        // return result
        return $this->getArrayResult((string) $search, $entity, $extra);
    }

    private function addQuery(string $key, QueryBuilder $builder): void
    {
        $sql = $builder->getQuery()->getSQL();
        if (\is_string($sql)) {
            $this->queries[$key] = $sql;
        }
    }

    /**
     * Create the SQL query for the calculation dates.
     *
     * @throws \ReflectionException
     */
    private function createCalculationDatesQuery(): self
    {
        $class = Calculation::class;
        if ($this->isGrantedSearch($class)) {
            $fields = ['date', 'createdAt', 'updatedAt'];
            foreach ($fields as $field) {
                $content = "date_format(e.$field, '%d.%m.%Y')";
                $key = $this->getKey($class, $field);
                $builder = $this->createQueryBuilder($class, $field, $content);
                $this->addQuery($key, $builder);
            }
        }

        return $this;
    }

    /**
     * Create the SQL query for the calculation groups.
     *
     * @throws \ReflectionException
     */
    private function createCalculationGroupQuery(): self
    {
        $class = Calculation::class;
        if ($this->isGrantedSearch($class)) {
            $field = 'group';
            $content = 'g.code';
            $key = $this->getKey($class, $field);
            $builder = $this->createQueryBuilder($class, $field, $content)
                ->join('e.groups', 'g');
            $this->addQuery($key, $builder);
        }

        return $this;
    }

    /**
     * Create the SQL query for the calculation items.
     *
     * @throws \ReflectionException
     */
    private function createCalculationItemQuery(): self
    {
        $class = Calculation::class;
        if ($this->isGrantedSearch($class)) {
            $field = 'item';
            $content = 'i.description';
            $key = $this->getKey($class, $field);
            $builder = $this->createQueryBuilder($class, $field, $content)
                ->join('e.groups', 'g')
                ->join('g.categories', 'c')
                ->join('c.items', 'i');
            $this->addQuery($key, $builder);
        }

        return $this;
    }

    /**
     * Create the SQL query for the calculation state.
     *
     * @throws \ReflectionException
     */
    private function createCalculationStateQuery(): self
    {
        $class = Calculation::class;
        if ($this->isGrantedSearch($class)) {
            $field = 'state';
            $content = 's.code';
            $key = $this->getKey($class, $field);
            $builder = $this->createQueryBuilder($class, $field, $content)
                ->join('e.state', 's');
            $this->addQuery($key, $builder);
        }

        return $this;
    }

    /**
     * Creates the SQL queries for the given entity.
     *
     * @param string   $class  the entity class
     * @param string[] $fields the entity fields to search in
     *
     * @psalm-param class-string $class
     *
     * @throws \ReflectionException
     */
    private function createEntityQueries(string $class, array $fields): self
    {
        // granted?
        if ($this->isGrantedSearch($class)) {
            foreach ($fields as $field) {
                $key = $this->getKey($class, $field);
                $builder = $this->createQueryBuilder($class, $field);
                $this->addQuery($key, $builder);
            }
        }

        return $this;
    }

    /**
     * Creates a query builder.
     *
     * @param string  $class   the entity class
     * @param string  $field   the field name
     * @param ?string $content the field content to search in or null to use the field name
     *
     * @psalm-param class-string $class
     *
     * @throws \ReflectionException
     */
    private function createQueryBuilder(string $class, string $field, ?string $content = null): QueryBuilder
    {
        $name = Utils::getShortName($class);
        $content ??= "e.$field";
        /** @psalm-var literal-string $from */
        $from = $class;
        /** @psalm-var literal-string $where */
        $where = "$content LIKE :" . self::SEARCH_PARAM;

        return $this->manager->createQueryBuilder()
            ->select('e.id')
            ->addSelect("'$name'")
            ->addSelect("'$field'")
            ->addSelect($content)
            ->from($from, 'e')
            ->where($where);
    }

    /**
     * Creates the native query and returns the array result.
     *
     * @param string  $search the term to search
     * @param ?string $entity the entity to search in or null for all
     * @param string  $extra  a SQL statement to add to the default native SELECT SQL statement
     *
     * @psalm-return SearchType[]
     *
     * @throws \ReflectionException
     */
    private function getArrayResult(string $search, ?string $entity = null, string $extra = ''): array
    {
        // queries:
        $queries = $this->getQueries();

        // entity?
        if (Utils::isString($entity)) {
            $queries = \array_filter($queries, fn (string $key): bool => 0 === \stripos($key, (string) $entity), \ARRAY_FILTER_USE_KEY);
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
        $query->setParameter(self::SEARCH_PARAM, "%$search%", Types::STRING);

        /** @psalm-var SearchType[] $result */
        $result = $query->getArrayResult();

        return $result;
    }

    /**
     * Gets the entity name for the given class.
     *
     * @param string $class the entity class
     *
     * @return string the entity name
     *
     * @psalm-param class-string $class
     *
     * @throws \ReflectionException
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
     *
     * @psalm-param class-string $class
     *
     * @throws \ReflectionException
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
     *
     * @throws \ReflectionException
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
            /** @psalm-var string $query */
            foreach ($this->queries as &$query) {
                // replace parameter
                $query = \str_replace('?', $param, $query);

                // replace column's names
                foreach ($columns as $index => $name) {
                    $query = \preg_replace("/AS \\w+[$index]/i", "AS $name", $query);
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
     * Returns if the given subject (entity class name) can be listed and displayed.
     */
    private function isGrantedSearch(string $subject): bool
    {
        return $this->isGrantedList($subject) && $this->isGrantedShow($subject);
    }
}
