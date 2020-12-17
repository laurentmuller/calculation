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

use App\Entity\CalculationCategory;
use App\Entity\Category;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for calculation group entity.
 *
 * @method CalculationCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method CalculationCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method CalculationCategory[]    findAll()
 * @method CalculationCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @author Laurent Muller
 *
 * @see \App\Entity\CalculationCategory
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
     */
    public function countCategoryReferences(Category $category): int
    {
        $result = $this->createQueryBuilder('e')
            ->select('DISTINCT COUNT(c.id)')
            ->innerJoin('e.group', 'g')
            ->innerJoin('g.calculation', 'c')
            ->where('e.category = :category')
            ->setParameter('category', $category)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }
}
