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
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @author Laurent Muller
 *
 * @see \App\Entity\Product
 */
class ProductRepository extends AbstractRepository
{
    /**
     * The alias for the category entity.
     */
    public const CATEGORY_ALIAS = 'c';

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
     * Count the number of products for the given category.
     *
     * @param Category $category the category to search for
     *
     * @return int the number of products
     */
    public function countCategoryReferences(Category $category): int
    {
        $result = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->innerJoin('e.category', 'c')
            ->where('c.id = :id')
            ->setParameter('id', $category->getId(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    /**
     * {@inheritdoc}
     */
    public function createDefaultQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        return parent::createDefaultQueryBuilder($alias)
            ->innerJoin("$alias.category", self::CATEGORY_ALIAS);
    }

    /**
     * Gets all products order by group, category and description.
     *
     * @return Product[]
     */
    public function findAllByGroup(): array
    {
        $builder = $this->createQueryBuilder('p')
            ->innerJoin('p.category', 'c')
            ->innerJoin('c.group', 'g')
            ->select('p')
            ->orderBy('g.code')
            ->addOrderBy('c.code')
            ->addOrderBy('p.description');

        return $builder->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchFields(string $field, string $alias = self::DEFAULT_ALIAS)
    {
        switch ($field) {
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
    public function getSortFields(string $field, string $alias = self::DEFAULT_ALIAS)
    {
        switch ($field) {
            case 'category.id':
            case 'category.code':
                return parent::getSortFields('code', self::CATEGORY_ALIAS);
            default:
                return parent::getSortFields($field, $alias);
        }
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
