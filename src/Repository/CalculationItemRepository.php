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

use App\Entity\CalculationItem;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for calculation item entity.
 *
 * @method CalculationItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method CalculationItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method CalculationItem[]    findAll()
 * @method CalculationItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @template-extends AbstractRepository<CalculationItem>
 *
 * @author Laurent Muller
 */
class CalculationItemRepository extends AbstractRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry The connections and entity managers registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CalculationItem::class);
    }
}
