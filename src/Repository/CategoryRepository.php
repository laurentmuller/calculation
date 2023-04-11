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
use App\Traits\GroupByTrait;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for category entity.
 *
 * @template-extends AbstractRepository<Category>
 *
 * @psalm-type DropDownType = array<string, array{id: int, categories: array<string, int>}>
 */
class CategoryRepository extends AbstractRepository
{
    use GroupByTrait;

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
     * The alias for the product entity.
     */
    private const PRODUCT_ALIAS = 'p';

    /**
     * The alias for the task entity.
     */
    private const TASK_ALIAS = 't';

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
     * @psalm-return DropDownType
     */
    public function getDropDownProducts(): array
    {
        $builder = $this->getDropDownQuery()
            ->innerJoin('c.products', 'p');

        return $this->mergeDropDown($builder);
    }

    /**
     * Gets categories used by the task table.
     *
     * <b>Note:</b> Only categories with at least one task are returned.
     *
     * @return array an array grouped by group name with the categories
     *
     * @psalm-return DropDownType
     */
    public function getDropDownTasks(): array
    {
        $builder = $this->getDropDownQuery()
            ->innerJoin('c.tasks', 't');

        return $this->mergeDropDown($builder);
    }

    /**
     * Gets the query builder for the list of categories sorted by the parent's group code and then by the code.
     *
     * @param int    $filterType the filter type to apply. One of the FILTER_* constants.
     * @param string $alias      the default entity alias
     *
     * @psalm-param literal-string $alias
     * @psalm-param self::FILTER_* $filterType
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
            'groupCode',
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
            'groupCode',
            'group.code' => parent::getSortField('code', self::GROUP_ALIAS),
            default => parent::getSortField($field, $alias),
        };
    }

    /**
     * Gets the query builder for the table.
     *
     * @psalm-param literal-string $alias
     */
    public function getTableQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->select("$alias.id")
            ->addSelect("$alias.code")
            ->addSelect("$alias.description")
            ->addSelect(self::GROUP_ALIAS . '.code as groupCode')
            ->addSelect($this->getCountDistinct(self::PRODUCT_ALIAS, 'products'))
            ->addSelect($this->getCountDistinct(self::TASK_ALIAS, 'tasks'))
            ->innerJoin("$alias.group", self::GROUP_ALIAS)
            ->leftJoin("$alias.products", self::PRODUCT_ALIAS)
            ->leftJoin("$alias.tasks", self::TASK_ALIAS)
            ->groupBy("$alias.id");
    }

    private function getDropDownQuery(): QueryBuilder
    {
        $group = self::GROUP_ALIAS . '.code';
        $groupid = self::GROUP_ALIAS . '.id';

        return $this->createQueryBuilder('c')
            ->select('c.id')
            ->addSelect('c.code')
            ->addSelect("$group AS group")
            ->addSelect("$groupid AS groupId")
            ->innerJoin('c.group', self::GROUP_ALIAS)
            ->groupBy('c.id')
            ->orderBy($group, Criteria::ASC)
            ->addOrderBy('c.code', Criteria::ASC);
    }

    /**
     * @psalm-return DropDownType
     */
    private function mergeDropDown(QueryBuilder $builder): array
    {
        /** @psalm-var array<array{
         *     group: string,
         *     groupId: int,
         *     code: string,
         *     id: int}> $values
         */
        $values = $builder->getQuery()->getArrayResult();
        if (\count($values) <= 1) {
            return [];
        }

        /** @psalm-var DropDownType $result */
        $result = [];
        foreach ($values as $value) {
            $key = $value['group'];
            if (!\array_key_exists($key, $result)) {
                $result[$key] = [
                    'id' => $value['groupId'],
                    'categories' => [],
                ];
            }
            $result[$key]['categories'][$value['code']] = $value['id'];
        }

        return $result;
    }
}
