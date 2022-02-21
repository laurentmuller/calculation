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

namespace App\Repository;

use App\Entity\Category;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;

/**
 * Abstract repository for products and tasks.
 *
 * @template T of \App\Entity\AbstractCategoryItemEntity
 * @template-extends AbstractRepository<T>
 *
 * @author Laurent Muller
 */
abstract class AbstractCategoryItemRepository extends AbstractRepository
{
    /**
     * The alias for the category entity.
     */
    public const CATEGORY_ALIAS = 'c';

    /**
     * Count the number of products or tasks for the given category.
     *
     * @param Category $category the category to search for
     *
     * @return int the number of products
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

    /**
     * {@inheritdoc}
     */
    public function createDefaultQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        return parent::createDefaultQueryBuilder($alias)
            ->innerJoin($alias . '.category', self::CATEGORY_ALIAS)
            ->innerJoin(self::CATEGORY_ALIAS . '.group', CategoryRepository::GROUP_ALIAS);
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchFields(string $field, string $alias = self::DEFAULT_ALIAS)
    {
        switch ($field) {
            case 'group.id':
                return parent::getSearchFields('id', CategoryRepository::GROUP_ALIAS);
            case 'group.code':
                return parent::getSearchFields('code', CategoryRepository::GROUP_ALIAS);
            case 'category.id':
                return parent::getSearchFields('id', self::CATEGORY_ALIAS);
            case 'category.code':
                return parent::getSearchFields('code', self::CATEGORY_ALIAS);
            default:
                return parent::getSearchFields($field, $alias);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSortField(string $field, string $alias = self::DEFAULT_ALIAS): string
    {
        switch ($field) {
            case 'group.id':
            case 'group.code':
                return parent::getSortField('code', CategoryRepository::GROUP_ALIAS);
            case 'category.id':
            case 'category.code':
                return parent::getSortField('code', self::CATEGORY_ALIAS);
            default:
                return parent::getSortField($field, $alias);
        }
    }
}
