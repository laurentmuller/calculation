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
use App\Traits\ArrayTrait;
use App\Traits\GroupByTrait;
use App\Traits\MathTrait;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for calculation state entity.
 *
 * @psalm-type QueryCalculationType = array{
 *      id: int,
 *      code: string,
 *      editable: boolean,
 *      color: string,
 *      count: int,
 *      items: float,
 *      total: float,
 *      margin_percent: float,
 *      margin_amount: float,
 *      percent_calculation: float,
 *      percent_amount: float}
 * @psalm-type DropDownType = array<int, array{
 *     id: int,
 *     icon: string,
 *     text: string,
 *     states: array<string, int>}>
 *
 * @template-extends AbstractRepository<CalculationState>
 */
class CalculationStateRepository extends AbstractRepository
{
    use ArrayTrait;
    use GroupByTrait;
    use MathTrait;

    /**
     * The alias for the calculation entity.
     */
    private const CALCULATION_ALIAS = 'c';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CalculationState::class);
    }

    /**
     * Gets states with the calculation statistics.
     *
     * <b>Note:</b> Only states with at least one calculation are returned.
     *
     * @return array the states with the number and the sum of calculations
     *
     * @psalm-return QueryCalculationType[]
     */
    public function getCalculations(): array
    {
        $builder = $this->createQueryBuilder('s')
            ->select('s.id')
            ->addSelect('s.code')
            ->addSelect('s.editable')
            ->addSelect('s.color')
            ->addSelect('COUNT(c.id) as count')
            ->addSelect('ROUND(SUM(c.itemsTotal), 2) as items')
            ->addSelect('ROUND(SUM(c.overallTotal), 2) as total')
            ->addSelect('ROUND(SUM(c.overallTotal) / sum(c.itemsTotal), 4) as margin_percent')
            ->addSelect('ROUND(SUM(c.overallTotal) - sum(c.itemsTotal), 2) as margin_amount')
            ->innerJoin('s.calculations', 'c')
            ->groupBy('s.id')
            ->orderBy('s.code', self::SORT_ASC);

        /** @psalm-var QueryCalculationType[] $result */
        $result = $builder->getQuery()->getArrayResult();
        $count = $this->getColumnSum($result, 'count');
        $total = $this->getColumnSum($result, 'total');
        foreach ($result as &$data) {
            $data['percent_calculation'] = $this->round($this->safeDivide($data['count'], $count), 5);
            $data['percent_amount'] = $this->round($this->safeDivide($data['total'], $total), 5);
        }

        return $result;
    }

    /**
     * Gets states used for the calculation table.
     *
     * <b>Note:</b> Only states with at least one calculation are returned.
     *
     * @return array an array grouped by editable with the states
     *
     * @psalm-return DropDownType
     */
    public function getDropDown(): array
    {
        return $this->mergeDropDown($this->getDropDownQuery());
    }

    /**
     * Gets states used for the calculation below table.
     *
     * <b>Note:</b> Only states with at least one calculation are returned.
     *
     * @param float $minMargin the minimum margin
     *
     * @return array an array grouped by editable with the states
     *
     * @psalm-return DropDownType
     */
    public function getDropDownBelow(float $minMargin): array
    {
        $builder = CalculationRepository::addBelowFilter(
            $this->getDropDownQuery(),
            $minMargin,
            self::CALCULATION_ALIAS
        );

        return $this->mergeDropDown($builder);
    }

    /**
     * Get the number of calculation states where editable is true.
     *
     * @throws ORMException
     */
    public function getEditableCount(): int
    {
        return $this->countStates(true);
    }

    /**
     * Gets query builder for the state where editable is true.
     *
     * @param literal-string $alias the entity alias
     *
     * @throws ORMException
     */
    public function getEditableQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->getSortedBuilder($alias)
            ->addCriteria($this->getEditableCriteria(true));
    }

    /**
     * Get the number of calculation states where editable is false.
     *
     * @throws ORMException
     */
    public function getNotEditableCount(): int
    {
        return $this->countStates(false);
    }

    /**
     * Gets query builder for the state where editable is false.
     *
     * @param literal-string $alias the entity alias
     *
     * @throws ORMException
     */
    public function getNotEditableQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->getSortedBuilder($alias)
            ->addCriteria($this->getEditableCriteria(false));
    }

    /**
     * Gets the query builder for the list of states sorted by the editable and code fields.
     *
     * @param literal-string $alias the entity alias
     */
    public function getQueryBuilderByEditable(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        $editField = $this->getSortField('editable', $alias);
        $codeField = $this->getSortField('code', $alias);

        return $this->createQueryBuilder($alias)
            ->orderBy($editField, self::SORT_ASC)
            ->addOrderBy($codeField, self::SORT_ASC);
    }

    /**
     * Gets the query builder for the list of states sorted by code.
     *
     * @param literal-string $alias the entity alias
     */
    public function getSortedBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        $field = $this->getSortField('code', $alias);

        return $this->createQueryBuilder($alias)
            ->orderBy($field, self::SORT_ASC);
    }

    public function getSortField(string $field, string $alias = self::DEFAULT_ALIAS): string
    {
        return match ($field) {
            'editable' => "IFELSE($alias.$field = 1, 0, 1)", // reverse
            default => parent::getSortField($field, $alias),
        };
    }

    /**
     * Gets the query builder for the table.
     *
     * @param literal-string $alias the entity alias
     */
    public function getTableQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->select("$alias.id")
            ->addSelect("$alias.code")
            ->addSelect("$alias.description")
            ->addSelect("$alias.editable")
            ->addSelect("$alias.color")
            ->addSelect($this->getCountDistinct(self::CALCULATION_ALIAS, 'calculations'))
            ->leftJoin("$alias.calculations", self::CALCULATION_ALIAS)
            ->groupBy("$alias.id");
    }

    /**
     * Get the number of calculation states for the given editable value.
     *
     * @throws ORMException
     */
    private function countStates(bool $editable): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('count(e.id)')
            ->addCriteria($this->getEditableCriteria($editable))
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getDropDownQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('s')
            ->select('s.id')
            ->addSelect('s.code')
            ->addSelect('s.editable')
            ->innerJoin('s.calculations', 'c')
            ->groupBy('s.id')
            ->orderBy('s.editable', self::SORT_DESC)
            ->addOrderBy('s.code', self::SORT_ASC);
    }

    private function getEditableCriteria(bool $editable): Criteria
    {
        return Criteria::create()
            ->where(Criteria::expr()->eq('editable', $editable));
    }

    /**
     * @psalm-return DropDownType
     */
    private function mergeDropDown(QueryBuilder $builder): array
    {
        /**
         * @psalm-var array<array{
         *     id: int,
         *     code: string,
         *     editable: bool}> $values
         */
        $values = $builder->getQuery()->getArrayResult();
        if (\count($values) <= 1) {
            return [];
        }

        /** @psalm-var DropDownType $result */
        $result = [];
        foreach ($values as $value) {
            $key = $value['editable'] ? 1 : -1;
            if (!\array_key_exists($key, $result)) {
                $result[$key] = [
                    'id' => $key,
                    'icon' => 1 === $key ? 'circle-check fa-lg far' : 'circle-xmark fa-lg far',
                    'text' => 1 === $key ? 'calculationstate.list.editable_1' : 'calculationstate.list.editable_0',
                    'states' => [],
                ];
            }
            $result[$key]['states'][$value['code']] = $value['id'];
        }

        return $result;
    }
}
