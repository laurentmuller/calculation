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
use App\Entity\Group;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;

/**
 * Abstract repository for products and tasks.
 *
 * @template T of \App\Entity\AbstractCategoryItemEntity
 *
 * @template-extends AbstractRepository<T>
 */
abstract class AbstractCategoryItemRepository extends AbstractRepository
{
    /**
     * The alias for the category entity.
     */
    final public const CATEGORY_ALIAS = 'c';

    /**
     * The alias for the group entity.
     */
    final public const GROUP_ALIAS = CategoryRepository::GROUP_ALIAS;

    /**
     * Count the number of products or tasks for the given category.
     *
     * @param Category $category the category to search for
     *
     * @return int the number of entities
     */
    public function countCategoryReferences(Category $category): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.category = :category')
            ->setParameter('category', $category->getId(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countGroupReferences(Group $group): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('DISTINCT COUNT(e.id)')
            ->innerJoin('e.category', 'c')
            ->innerJoin('c.group', 'g')
            ->where('g.id = :group')
            ->setParameter('group', $group->getId(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function createDefaultQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        return parent::createDefaultQueryBuilder($alias)
            ->innerJoin("$alias.category", self::CATEGORY_ALIAS)
            ->innerJoin(self::CATEGORY_ALIAS . '.group', self::GROUP_ALIAS)
            ->addSelect(self::CATEGORY_ALIAS)
            ->addSelect(self::GROUP_ALIAS);
    }

    public function getSearchFields(string $field, string $alias = self::DEFAULT_ALIAS): array|string
    {
        return match ($field) {
            'group.id' => parent::getSearchFields('id', self::GROUP_ALIAS),
            'groupCode',
            'group.code' => parent::getSearchFields('code', self::GROUP_ALIAS),
            'category.id' => parent::getSearchFields('id', self::CATEGORY_ALIAS),
            'categoryCode',
            'category.code' => parent::getSearchFields('code', self::CATEGORY_ALIAS),
            default => parent::getSearchFields($field, $alias),
        };
    }

    public function getSortField(string $field, string $alias = self::DEFAULT_ALIAS): string
    {
        return match ($field) {
            'group.id',
            'groupCode',
            'group.code' => parent::getSortField('code', self::GROUP_ALIAS),
            'category.id',
            'categoryCode',
            'category.code' => parent::getSortField('code', self::CATEGORY_ALIAS),
            default => parent::getSortField($field, $alias),
        };
    }

    /**
     * Create the query builder for the table.
     *
     * @param literal-string $alias the entity alias
     */
    protected function createTableQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->select("$alias.id")
            ->addSelect("$alias.unit")
            ->addSelect("$alias.supplier")
            ->addSelect(self::CATEGORY_ALIAS . '.code as categoryCode')
            ->addSelect(self::GROUP_ALIAS . '.code as groupCode')
            ->innerJoin("$alias.category", self::CATEGORY_ALIAS)
            ->innerJoin(self::CATEGORY_ALIAS . '.group', self::GROUP_ALIAS);
    }
}
