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

use App\Entity\Task;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for task entity.
 *
 * @template-extends AbstractCategoryItemRepository<Task>
 */
class TaskRepository extends AbstractCategoryItemRepository
{
    /**
     * The alias for the task item entity.
     */
    private const ITEM_ALIAS = 'i';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function getSearchFields(string $field, string $alias = self::DEFAULT_ALIAS): array|string
    {
        return match ($field) {
            'categoryCode',
            'category.code' => parent::getSearchFields('code', self::CATEGORY_ALIAS),
            'groupCode',
            'group.code' => parent::getSearchFields('code', self::GROUP_ALIAS),
            default => parent::getSearchFields($field, $alias),
        };
    }

    /**
     * Gets the query builder for the list of tasks sorted by name.
     *
     * @param bool           $all   true to return all, false to return only tasks that contains at least one operation
     *                              with one margin
     * @param literal-string $alias the entity alias
     */
    public function getSortedBuilder(bool $all = true, string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        $field = $this->getSortField('name', $alias);
        $builder = $this->createQueryBuilder($alias)
            ->orderBy($field, Criteria::ASC);
        if (!$all) {
            $builder->innerJoin("$alias.items", 'item')
                ->innerJoin('item.margins', 'margin')
                ->groupBy($field);
        }

        return $builder;
    }

    public function getSortField(string $field, string $alias = self::DEFAULT_ALIAS): string
    {
        return match ($field) {
            'categoryCode',
            'category.code' => parent::getSortField('code', self::CATEGORY_ALIAS),
            'groupCode',
            'group.code' => parent::getSortField('color', self::GROUP_ALIAS),
            default => parent::getSortField($field, $alias),
        };
    }

    /**
     * Gets the query builder for the table.
     *
     * @param literal-string $alias the entity alias
     */
    public function getTableQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->select("$alias.id")
            ->addSelect("$alias.name")
            ->addSelect("$alias.unit")
            ->addSelect("$alias.supplier")
            ->addSelect(self::CATEGORY_ALIAS . '.code as categoryCode')
            ->addSelect(self::GROUP_ALIAS . '.code as groupCode')
            ->addSelect($this->getCountDistinct(self::ITEM_ALIAS, 'items'))
            ->innerJoin("$alias.category", self::CATEGORY_ALIAS)
            ->innerJoin(self::CATEGORY_ALIAS . '.group', self::GROUP_ALIAS)
            ->leftJoin("$alias.items", self::ITEM_ALIAS)
            ->groupBy("$alias.id");
    }
}
