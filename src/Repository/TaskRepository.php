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
use App\Entity\Task;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for task entity.
 *
 * @method Task|null find($id, $lockMode = null, $lockVersion = null)
 * @method Task|null findOneBy(array $criteria, array $orderBy = null)
 * @method Task[]    findAll()
 * @method Task[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @author Laurent Muller
 *
 * @see \App\Entity\Task
 * @template-extends AbstractRepository<Task>
 */
class TaskRepository extends AbstractRepository
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
        parent::__construct($registry, Task::class);
    }

    /**
     * Count the number of tasks for the given category.
     *
     * @param Category $category the category to search for
     *
     * @return int the number of tasks
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
     * Gets the query builder for the list of tasks sorted by name.
     *
     * @param bool   $all   true to return all, false to return only tasks that contains at least one operation with one margin
     * @param string $alias the default entity alias
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

    /**
     * {@inheritdoc}
     */
    public function getSortField(string $field, string $alias = self::DEFAULT_ALIAS): string
    {
        switch ($field) {
            case 'category.id':
            case 'category.code':
                return parent::getSortField('code', self::CATEGORY_ALIAS);
            default:
                return parent::getSortField($field, $alias);
        }
    }
}
