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

namespace App\Repository;

use App\Attribute\SortableEntity;
use App\Interfaces\EntityInterface;
use App\Interfaces\SortModeInterface;
use App\Utils\StringUtils;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

/**
 * Base repository.
 *
 * @template TEntity of EntityInterface
 *
 * @extends ServiceEntityRepository<TEntity>
 */
abstract class AbstractRepository extends ServiceEntityRepository implements SortModeInterface
{
    /** The alias for the calculation entity. */
    public const string CALCULATION_ALIAS = 'c';

    /** The alias for the category entity. */
    public const string CATEGORY_ALIAS = 'c';

    /** The default entity alias used to create the query builder (value = 'e'). */
    public const string DEFAULT_ALIAS = 'e';

    /** The alias for the group entity. */
    public const string GROUP_ALIAS = 'g';

    /** The alias for the product entity. */
    public const string PRODUCT_ALIAS = 'p';

    /** The alias for the state entity. */
    public const string STATE_ALIAS = 's';

    /** The alias for the task entity. */
    public const string TASK_ALIAS = 't';

    /** The alias for the task item entity. */
    public const string TASK_ITEM_ALIAS = 'i';

    /**
     * Creates a default query builder.
     *
     * @param string $alias the entity alias
     */
    public function createDefaultQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->createQueryBuilder($alias);
    }

    /**
     * Flushes all changes to objects that have been queued to the database.
     *
     * This effectively synchronizes the in-memory state of managed objects with the
     * database.
     */
    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * Gets the default order of this entity.
     *
     * @return array<string, string> an array with the field as the key and the order as the value.
     *                               An empty array is returned if no attribute is found.
     *
     * @throws \ReflectionException if the class does not exist or if the validation parameter
     *                              is true and a property name is not found
     */
    public function getDefaultOrder(): array
    {
        return SortableEntity::getOrder($this->getEntityName());
    }

    /**
     * Gets sorted, distinct and not null values for the given column.
     *
     * @param string $field the field name (column) to get values for
     * @param string $value a value to search within the column or an empty string for all
     * @param int    $limit the maximum number of results to retrieve (the 'limit') or -1 for all
     *
     * @return array an array, maybe empty, of matching values
     */
    public function getDistinctValues(string $field, string $value = '', int $limit = -1): array
    {
        $name = \sprintf('%s.%s', self::DEFAULT_ALIAS, $field);
        $builder = $this->createQueryBuilder(self::DEFAULT_ALIAS)
            ->select($name)
            ->distinct()
            ->orderBy($name);
        if (StringUtils::isString($value)) {
            $param = 'search';
            $builder->where(\sprintf('%s LIKE :%s', $name, $param))
                ->setParameter($param, \sprintf('%%%s%%', $value));
        } else {
            $builder->where($name . ' IS NOT NULL');
        }
        if ($limit > 0) {
            $builder->setMaxResults($limit);
        }

        return $builder->getQuery()
            ->getSingleColumnResult();
    }

    /**
     * Gets the database search fields.
     *
     * The default implementation returns the alias and the field separated by a dot character ('.').
     *
     * @param string $field the field name
     * @param string $alias the entity alias
     *
     * @return string|string[] one on more database search fields
     */
    public function getSearchFields(string $field, string $alias = self::DEFAULT_ALIAS): array|string
    {
        return \sprintf('%s.%s', $alias, $field);
    }

    /**
     * Creates a search query.
     *
     * @param array<string, string>  $sortedFields the sorted fields where key is the field name, and value is the sort
     *                                             mode ('ASC' or 'DESC')
     * @param array<Criteria|string> $criteria     the filter criteria (the where clause)
     * @param string                 $alias        the entity alias
     *
     * @see AbstractRepository::createDefaultQueryBuilder()
     *
     * @phpstan-return Query<null, mixed>
     */
    public function getSearchQuery(
        array $sortedFields = [],
        array $criteria = [],
        string $alias = self::DEFAULT_ALIAS
    ): Query {
        $builder = $this->createDefaultQueryBuilder($alias);
        foreach ($criteria as $criterion) {
            if ($criterion instanceof Criteria) {
                $builder->addCriteria($criterion);
            } else {
                $builder->andWhere($criterion);
            }
        }
        foreach ($sortedFields as $name => $order) {
            $field = $this->getSortField($name, $alias);
            $builder->addOrderBy($field, $order);
        }

        return $builder->getQuery();
    }

    /**
     * Gets the name of the single identifier field.
     *
     * Note that this only works on entity classes that have a single-field primary key.
     *
     * @throws MappingException if the class doesn't have an identifier, or it has a composite primary key
     */
    public function getSingleIdentifierFieldName(): string
    {
        return $this->getClassMetadata()->getSingleIdentifierFieldName();
    }

    /**
     * Gets the database sort field.
     *
     * The default implementation returns the alias and the field separated by a dot character ('.').
     *
     * @param string $field the field name
     * @param string $alias the entity alias
     *
     * @return string the sort field
     */
    public function getSortField(string $field, string $alias = self::DEFAULT_ALIAS): string
    {
        return \sprintf('%s.%s', $alias, $field);
    }

    /**
     * Persist the given entity to the database.
     *
     * @param EntityInterface $entity the entity to persist
     * @param bool            $flush  true to flush change to the database
     *
     * @see AbstractRepository::flush()
     */
    public function persist(EntityInterface $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->flush();
        }
    }

    /**
     * Remove the given entity from the database.
     *
     * @param EntityInterface $entity the entity to remove
     * @param bool            $flush  true to flush change to the database
     */
    public function remove(EntityInterface $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->flush();
        }
    }

    /**
     * Add alias to the given fields.
     *
     * @param string   $alias the entity alias
     * @param string[] $names the fields to add alias
     *
     * @return string[] the fields with alias
     */
    protected function addPrefixes(string $alias, array $names): array
    {
        return \array_map(static fn (string $name): string => \sprintf('%s.%s', $alias, $name), $names);
    }

    /**
     * Concat fields.
     *
     * @param string   $alias   the entity prefix
     * @param string[] $fields  the fields to concat
     * @param string   $default the default value to use when a field is null
     *
     * @return string the concatenated fields
     */
    protected function concat(string $alias, array $fields, string $default = ''): string
    {
        $values = \array_map(
            static fn (string $field): string => \sprintf("COALESCE(%s.%s, '%s')", $alias, $field, $default),
            $fields
        );

        return \sprintf('CONCAT(%s)', \implode(',', $values));
    }

    /**
     * Gets the count distinct clause.
     *
     * @param string $alias the table alias
     * @param string $field the target field name
     */
    protected function getCountDistinct(string $alias, string $field): string
    {
        return \sprintf('COUNT(DISTINCT %s.id) AS %s', $alias, $field);
    }
}
