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
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for category entity.
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @template-extends AbstractRepository<Category>
 *
 * @author Laurent Muller
 */
class CategoryRepository extends AbstractRepository
{
    /**
     * The alias for the group entity.
     */
    public const GROUP_ALIAS = 'g';

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
            ->innerJoin("$alias.group", self::GROUP_ALIAS);
    }

    /**
     * Gets categories with the number of products.
     *
     * <b>Note:</b> Only categories with at least one product are returned.
     *
     * @return array an array with the category and the number of products
     */
    public function getListCountProducts(): array
    {
        $builder = $this->createQueryBuilder('c')
            ->select('c.id')
            ->addSelect('c.code')
            ->addSelect('c.description')
            ->addSelect('COUNT(p.id) as count')
            ->innerJoin('c.products', 'p')
            ->groupBy('c.id')
            ->orderBy('c.code', Criteria::ASC);

        return $builder->getQuery()->getArrayResult();
    }

    /**
     * Gets categories with the number of tasks.
     *
     * <b>Note:</b> Only categories with at least one task are returned.
     *
     * @return array an array with the category and the number of tasks
     */
    public function getListCountTasks(): array
    {
        $builder = $this->createQueryBuilder('c')
            ->select('c.id')
            ->addSelect('c.code')
            ->addSelect('c.description')
            ->addSelect('COUNT(t.id) as count')
            ->innerJoin('c.tasks', 't')
            ->groupBy('c.id')
            ->orderBy('c.code', Criteria::ASC);

        return $builder->getQuery()->getArrayResult();
    }

    /**
     * Gets the query builder for the list of categories sorted by the parent's group code and then by the code.
     *
     * @param string $alias the default entity alias
     */
    public function getQueryBuilderByGroup(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        $field = $this->getSortField('code', $alias);
        $groupField = self::GROUP_ALIAS . '.code';

        return $this->createQueryBuilder($alias)
            ->innerJoin("$alias.group", self::GROUP_ALIAS)
            ->orderBy($groupField)
            ->addOrderBy($field);
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchFields(string $field, string $alias = self::DEFAULT_ALIAS)
    {
        switch ($field) {
            case 'group.id':
                return parent::getSearchFields('id', self::GROUP_ALIAS);
            case 'group.code':
                return parent::getSearchFields('code', self::GROUP_ALIAS);
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
                return parent::getSortField('code', self::GROUP_ALIAS);
            default:
                return parent::getSortField($field, $alias);
        }
    }
}
