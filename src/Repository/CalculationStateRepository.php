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

use App\Entity\CalculationState;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for calculation state entity.
 *
 * @template-extends AbstractRepository<CalculationState>
 * @psalm-suppress  MixedReturnTypeCoercion
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
     * Gets states used for the calculation table.
     *
     * <b>Note:</b> Only states with at least one calculation are returned.
     *
     * @return array an array with the state and the number of calculations
     */
    public function getDropDownStates(): array
    {
        $builder = $this->createQueryBuilder('s')
            ->select('s.id')
            ->addSelect('s.code')
            ->addSelect('s.editable')
            ->innerJoin('s.calculations', 'c')
            ->groupBy('s.id')
            ->orderBy('s.editable', Criteria::DESC)
            ->addOrderBy('s.code', Criteria::ASC);

        return $builder->getQuery()->getArrayResult();
    }

    /**
     * @psalm-param literal-string $alias
     */
    public function getEditableQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        /** @var literal-string $where */
        $where = "$alias.editable = 1";

        return $this->getSortedBuilder($alias)
            ->where($where);
    }

    /**
     * Gets the list of calculation states sorted by code.
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
     * Gets states with the number and the sum (overall total) of calculations with overall margin below.
     *
     * <b>Note:</b> Only states with at least one calculation are returned.
     *
     * @param float $margin the minimum margin
     *
     * @return array the states with the number and the sum of calculations
     * @psalm-return array<array{
     *      id: int,
     *      code: string,
     *      editable: boolean,
     *      color: string,
     *      count: int,
     *      items: float,
     *      total: float,
     *      margin: float,
     *      marginAmount: float}>
     */
    public function getListCountBelows(float $margin): array
    {
        $builder = $this->getListCountQueryBuilder()
            ->where('c.itemsTotal != 0')
            ->andWhere('(c.overallTotal / c.itemsTotal) < :margin')
            ->setParameter('margin', $margin, Types::FLOAT);

        $results = $builder->getQuery()->getArrayResult();

        /** @psalm-var array{
         *      id: int,
         *      code: string,
         *      editable: boolean,
         *      color: string,
         *      count: int,
         *      items: float,
         *      total: float,
         *      margin: float,
         *      marginAmount: float} $result
         */
        foreach ($results as &$result) {
            $this->updateQueryResult($result);
        }

        return $results;
    }

    /**
     * Gets states with the number and the sum (overall total) of calculations.
     *
     * <b>Note:</b> Only states with at least one calculation are returned.
     *
     * @return array the states with the number and the sum of calculations
     * @psalm-return array<array{
     *      id: int,
     *      code: string,
     *      editable: boolean,
     *      color: string,
     *      count: int,
     *      items: float,
     *      total: float,
     *      margin: float,
     *      marginAmount: float}>
     */
    public function getListCountCalculations(): array
    {
        $results = $this->getListCountQueryBuilder()
            ->getQuery()
            ->getArrayResult();

        /** @psalm-var array{
         *      id: int,
         *      code: string,
         *      editable: boolean,
         *      color: string,
         *      count: int,
         *      items: string|float,
         *      total: string|float,
         *      margin: string|float,
         *      marginAmount: string|float} $result
         */
        foreach ($results as &$result) {
            $this->updateQueryResult($result);
        }

        return $results;
    }

    /**
     * @psalm-param literal-string $alias
     */
    public function getNotEditableQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        /** @var literal-string $where */
        $where = "$alias.editable = 0";

        return $this->getSortedBuilder($alias)
            ->where($where);
    }

    /**
     * Gets the query builder for the list of states sorted by the editable and the code fields.
     *
     * @param string $alias the default entity alias
     * @psalm-param literal-string $alias
     */
    public function getQueryBuilderByEditable(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        $editField = $this->getSortField('editable', $alias);
        $codeField = $this->getSortField('code', $alias);

        return $this->createQueryBuilder($alias)
            ->orderBy($editField, Criteria::DESC)
            ->addOrderBy($codeField, Criteria::ASC);
    }

    /**
     * Gets the query builder for the list of states sorted by code.
     *
     * @param string $alias the default entity alias
     * @psalm-param literal-string $alias
     */
    public function getSortedBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        $field = $this->getSortField('code', $alias);

        return $this->createQueryBuilder($alias)
            ->orderBy($field, Criteria::ASC);
    }

    /**
     * Gets query builder with the number and the sum (overall total) of calculations.
     */
    private function getListCountQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('s')
            ->select('s.id')
            ->addSelect('s.code')
            ->addSelect('s.editable')
            ->addSelect('s.color')
            ->addSelect('COUNT(c.id) as count')
            ->addSelect('SUM(c.itemsTotal) as items')
            ->addSelect('SUM(c.overallTotal) as total')
            ->addSelect('SUM(c.overallTotal) / sum(c.itemsTotal) as margin')
            ->addSelect('SUM(c.overallTotal) - sum(c.itemsTotal) as marginAmount')
            ->innerJoin('s.calculations', 'c')
            ->groupBy('s.id')
            ->orderBy('s.code', Criteria::ASC);
    }

    /**
     * @psalm-param array{
     *      id: int,
     *      code: string,
     *      editable: boolean,
     *      color: string,
     *      count: int,
     *      items: string|float,
     *      total: string|float,
     *      margin: string|float,
     *      marginAmount: string|float} $result
     */
    private function updateQueryResult(array &$result): void
    {
        $result['total'] = (float) $result['total'];
        $result['items'] = (float) $result['items'];
        $result['margin'] = (float) $result['margin'];
        $result['marginAmount'] = (float) $result['marginAmount'];
    }
}
