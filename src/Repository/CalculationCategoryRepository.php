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

use App\Entity\CalculationCategory;
use App\Entity\Category;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for calculation group entity.
 *
 * @template-extends AbstractRepository<CalculationCategory>
 */
class CalculationCategoryRepository extends AbstractRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry The connections and entity managers registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CalculationCategory::class);
    }

    /**
     * Count the number of calculations for the given category.
     *
     * @param Category $category the category to search for
     *
     * @return int the number of calculations
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countCategoryReferences(Category $category): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('DISTINCT COUNT(c.id)')
            ->innerJoin('e.group', 'g')
            ->innerJoin('g.calculation', 'c')
            ->where('e.category = :category')
            ->setParameter('category', $category->getId(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
