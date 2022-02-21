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
use App\Entity\Product;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for product entity.
 *
 * @template-extends AbstractCategoryItemRepository<Product>
 * @psalm-suppress  MixedReturnTypeCoercion
 *
 * @author Laurent Muller
 */
class ProductRepository extends AbstractCategoryItemRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry The connections and entity managers registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Gets all products order by group, category and description.
     *
     * @return Product[]
     */
    public function findAllByGroup(): array
    {
        $groupField = $this->getSortField('group.code');
        $categoryField = $this->getSortField('category.code');
        $descriptionField = $this->getSortField('description');

        $builder = $this->createDefaultQueryBuilder()
            ->orderBy($groupField)
            ->addOrderBy($categoryField)
            ->addOrderBy($descriptionField);

        return $builder->getQuery()->getResult();
    }

    /**
     * Gets all products for the given category.
     *
     * @param Category $category     the category to serach products for
     * @param bool     $excludeEmpty true to exclude products with an empty price (0.00)
     *
     * @return Product[] the products
     */
    public function findByCategory(Category $category, bool $excludeEmpty = true): array
    {
        $builder = $this->createDefaultQueryBuilder('e')
            ->where('e.category = :category')
            ->setParameter('category', $category->getId(), Types::INTEGER)
            ->orderBy('e.description');

        if ($excludeEmpty) {
            $builder->andWhere('e.price != 0');
        }

        return $builder
            ->getQuery()
            ->getResult();
    }

    /**
     * Gets the default query builder with all products order by the category code, the group code and the product description.
     */
    public function getQueryBuilderByCategory(): QueryBuilder
    {
        return $this->createDefaultQueryBuilder()
            ->addOrderBy(self::CATEGORY_ALIAS . '.code')
            ->addOrderBy(CategoryRepository::GROUP_ALIAS . '.code')
            ->addOrderBy(self::DEFAULT_ALIAS . '.description');
    }

    /**
     * Search products (used by calculation to add a new item).
     *
     * @param string $value      the search term
     * @param int    $maxResults the maximum number of results to retrieve (the "limit")
     *
     * @return array an array, maybe empty, of matching products
     */
    public function search(string $value, int $maxResults = 15): array
    {
        $builder = $this->createQueryBuilder('p')
            ->select('p.description')
            ->addSelect('p.unit')
            ->addSelect('p.price')
            ->addSelect('c.id as categoryId')
            ->addSelect("CONCAT(c.code, ' - ', g.code) AS category")
            ->innerJoin('p.category', 'c')
            ->innerJoin('c.group', 'g')
            ->orderBy('c.code')
            ->addOrderBy('p.description')
            ->setMaxResults($maxResults);

        // where clause
        $param = ':search';
        $expr = $builder->expr();
        $or = $expr->orx(
            $expr->like('p.description', $param),
            $expr->like('c.code', $param),
            $expr->like('g.code', $param),
        );
        $builder->where($or)
            ->setParameter($param, "%{$value}%", Types::STRING);

        return $builder->getQuery()->getArrayResult();
    }
}
