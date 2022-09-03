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

use App\Entity\Category;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for category entity.
 *
 * @template-extends AbstractRepository<Category>
 */
class CategoryRepository extends AbstractRepository
{
    /**
     * The filter type to display all categories.
     */
    final public const FILTER_NONE = 0;

    /**
     * The filter type to display only categories that contain one or more products.
     */
    final public const FILTER_PRODUCTS = 1;

    /**
     * The filter type to display only categories that contain one or more tasks.
     */
    final public const FILTER_TASKS = 2;

    /**
     * The alias for the group entity.
     */
    final public const GROUP_ALIAS = 'g';

    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry The connections and entity managers registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * {@inheritdoc}
     */
    public function createDefaultQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        return parent::createDefaultQueryBuilder($alias)
            ->innerJoin("$alias.group", self::GROUP_ALIAS)
            ->addSelect(self::GROUP_ALIAS);
    }

    /**
     * Gets categories used for the product table.
     *
     * <b>Note:</b> Only categories with at least one product are returned.
     *
     * @return array an array with the categories
     */
    public function getDropDownProducts(): array
    {
        $builder = $this->getDropDownQuery()
            ->innerJoin('c.products', 'p');

        return $builder->getQuery()->getArrayResult();
    }

    /**
     * Gets categories used by the task table.
     *
     * <b>Note:</b> Only categories with at least one task are returned.
     *
     * @return array an array with the categories
     */
    public function getDropDownTasks(): array
    {
        return $this->getDropDownQuery()
            ->innerJoin('c.tasks', 't')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Gets the query builder for the list of categories sorted by the parent's group code and then by the code.
     *
     * @param int    $filterType the filter type to apply. One of the FILTER_* constants.
     * @param string $alias      the default entity alias
     *
     * @psalm-param literal-string $alias
     * @psalm-param (self::FILTER_NONE | self::FILTER_PRODUCTS | self::FILTER_TASKS) $filterType
     */
    public function getQueryBuilderByGroup(int $filterType = self::FILTER_NONE, string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        $groupField = $this->getSortField('group.code', $alias);
        $codeField = $this->getSortField('code', $alias);
        $builder = $this->createQueryBuilder($alias)
            ->innerJoin("$alias.group", self::GROUP_ALIAS)
            ->orderBy($groupField, Criteria::ASC)
            ->addOrderBy($codeField, Criteria::ASC);

        return match ($filterType) {
            self::FILTER_PRODUCTS => $builder->innerJoin("$alias.products", 'p'),
            self::FILTER_TASKS => $builder->innerJoin("$alias.tasks", 't'),
            default => $builder
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchFields(string $field, string $alias = self::DEFAULT_ALIAS): array|string
    {
        return match ($field) {
            'group.id' => parent::getSearchFields('id', self::GROUP_ALIAS),
            'group.code' => parent::getSearchFields('code', self::GROUP_ALIAS),
            default => parent::getSearchFields($field, $alias),
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getSortField(string $field, string $alias = self::DEFAULT_ALIAS): string
    {
        return match ($field) {
            'group.id',
            'group.code' => parent::getSortField('code', self::GROUP_ALIAS),
            default => parent::getSortField($field, $alias),
        };
    }

    private function getDropDownQuery(): QueryBuilder
    {
        $group = self::GROUP_ALIAS . '.code';

        return $this->createQueryBuilder('c')
            ->select('c.id')
            ->addSelect('c.code')
            ->addSelect("$group AS group")
            ->innerJoin('c.group', self::GROUP_ALIAS)
            ->groupBy('c.id')
            ->orderBy($group, Criteria::ASC)
            ->addOrderBy('c.code', Criteria::ASC);
    }
}
