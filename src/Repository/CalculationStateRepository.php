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

use App\Entity\CalculationState;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for calculation state entity.
 *
 * @method CalculationState|null find($id, $lockMode = null, $lockVersion = null)
 * @method CalculationState|null findOneBy(array $criteria, array $orderBy = null)
 * @method CalculationState[]    findAll()
 * @method CalculationState[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @author Laurent Muller
 *
 * @see \App\Entity\CalculationState
 * @template-extends AbstractRepository<CalculationState>
 */
class CalculationStateRepository extends AbstractRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry The connections and entity managers registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CalculationState::class);
    }

    /**
     * Gets the the list of calculation states sorted by code.
     *
     * @return CalculationState[] the calculation states
     */
    public function getList(): array
    {
        return $this->getSortedBuilder()
            ->getQuery()
            ->getResult();
    }

    /**
     * Gets states with the number and the sum (overall total) of calculations.
     *
     * <b>Note:</b> Only states with at least one calculation are returned.
     *
     * @return array the states with the number and the sum of calculations
     */
    public function getListCount(): array
    {
        return $this->getListCountQueryBuilder()
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Gets states with the number and the sum (overall total) of calculations with overall margin below.
     *
     * <b>Note:</b> Only states with at least one calculation are returned.
     *
     * @param float $margin the minimumn margin
     *
     * @return array the states with the number and the sum of calculations
     */
    public function getListCountBelow(float $margin): array
    {
        $builder = $this->getListCountQueryBuilder()
            ->where('c.itemsTotal != 0')
            ->andWhere('(c.overallTotal / c.itemsTotal) - 1 < :margin')
            ->setParameter('margin', $margin, Types::FLOAT);

        return $builder->getQuery()->getArrayResult();
    }

    /**
     * Gets the query builder for the list of states sorted by code.
     *
     * @param string $alias the default entity alias
     */
    public function getSortedBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        $field = (string) $this->getSortFields('code', $alias);

        return $this->createQueryBuilder($alias)
            ->orderBy($field, Criteria::ASC);
    }

    /**
     * Gets query builder with the number and the sum (overall total) of calculations.
     */
    private function getListCountQueryBuilder(): QueryBuilder
    {
        $builder = $this->createQueryBuilder('s')
            ->select('s.id')
            ->addSelect('s.code')
            ->addSelect('s.editable')
            ->addSelect('s.color')
            ->addSelect('COUNT(c.id)         as count')
            ->addSelect('SUM(c.overallTotal) as total')
            ->innerJoin('s.calculations', 'c')
            ->groupBy('s.id')
            ->orderBy('s.code', Criteria::ASC);

        return $builder;
    }
}
