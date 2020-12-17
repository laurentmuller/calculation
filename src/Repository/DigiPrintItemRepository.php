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

use App\Entity\DigiPrintItem;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for digi print item entity.
 *
 * @method DigiPrintItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method DigiPrintItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method DigiPrintItem[]    findAll()
 * @method DigiPrintItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @author Laurent Muller
 *
 * @see \App\Entity\DigiPrintItem
 */
class DigiPrintItemRepository extends AbstractRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry The connections and entity managers registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DigiPrintItem::class);
    }
}
