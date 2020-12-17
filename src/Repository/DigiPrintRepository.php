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
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for DigiPrint entity.
 *
 * @method DigiPrint|null find($id, $lockMode = null, $lockVersion = null)
 * @method DigiPrint|null findOneBy(array $criteria, array $orderBy = null)
 * @method DigiPrint[]    findAll()
 * @method DigiPrint[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
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
     * Gets all DigiPrint sorted by format.
     *
     * @return DigiPrint[]
     */
    public function findAllByFormat(): array
    {
        return $this->findBy([], ['format' => Criteria::ASC]);
    }

    /**
     * Gets the query builder for the list of DigiPrint sorted by format.
     *
     * @param string $alias the default entity alias
     */
    public function getSortedBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        $field = $this->getSortFields('format', $alias);

        return $this->createQueryBuilder($alias)
            ->orderBy($field, Criteria::ASC);
    }
}
