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

use App\Entity\DigiPrint;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for digi print entity.
 *
 * @author Laurent Muller
 *
 * @see \App\Entity\DigiPrint
 */
class DigiPrintRepository extends AbstractRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry The connections and entity managers registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DigiPrint::class);
    }

    /**
     * Gets all digi print order by format.
     *
     * @return DigiPrint[]
     */
    public function findAllByFormat(): array
    {
        return $this->findBy([], ['format' => Criteria::ASC]);
    }
}
