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
 *      percent_calculation :float,
 *      percent_amount:float}
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
     * Gets states with calculations statistics.
     *
     * <b>Note:</b> Only states with at least one calculation are returned.
     *
     * @return array the states with the number and the sum of calculations
     *
     * @psalm-return QueryCalculationType[]
     */
    public function getCalculations(): array
    {
        $result = $this->getCalculationsQueryBuilder()
            ->getQuery()
            ->getArrayResult();

        $count = $this->getColumnSum($result, 'count');
        $total = $this->getColumnSum($result, 'total');
        foreach ($result as &$data) {
            $data['percent_calculation'] = $this->safeDivide($data['count'], $count);
            $data['percent_amount'] = $this->safeDivide($data['total'], $total);
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
        $builder = $this->getDropDownQuery();
        $builder = CalculationRepository::addBelowFilter($builder, $minMargin, 'c');

        return $this->mergeDropDown($builder);
    }

    /**
     * Get the number of calculation states where editable is true.
     *
     * @param literal-string $alias the entity alias
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function getEditableCount(string $alias = self::DEFAULT_ALIAS): int
    {
        return $this->countStates($this->getEditableQueryBuilder($alias), $alias);
    }

    /**
     * Gets query builder for state where editable is true.
     *
     * @param literal-string $alias the entity alias
     */
    public function getEditableQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->getSortedBuilder($alias)
            ->where("$alias.editable = 1");
    }

    /**
     * Get the number of calculation states where editable is false.
     *
     * @param literal-string $alias the entity alias
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function getNotEditableCount(string $alias = self::DEFAULT_ALIAS): int
    {
        return $this->countStates($this->getNotEditableQueryBuilder($alias), $alias);
    }

    /**
     * Gets query builder for state where editable is false.
     *
     * @param literal-string $alias the entity alias
     */
    public function getNotEditableQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->getSortedBuilder($alias)
            ->where("$alias.editable = 0");
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
            ->orderBy($editField, Criteria::ASC)
            ->addOrderBy($codeField, Criteria::ASC);
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
            ->orderBy($field, Criteria::ASC);
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
     * @param literal-string $alias the entity alias
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    private function countStates(QueryBuilder $builder, string $alias): int
    {
        return (int) $builder->select("count($alias.id)")
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Gets the query builder for calculations statistics.
     */
    private function getCalculationsQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('s')
            ->select('s.id')
            ->addSelect('s.code')
            ->addSelect('s.editable')
            ->addSelect('s.color')
            ->addSelect('COUNT(c.id) as count')
            ->addSelect('SUM(c.itemsTotal) as items')
            ->addSelect('SUM(c.overallTotal) as total')
            ->addSelect('SUM(c.overallTotal) / sum(c.itemsTotal) as margin_percent')
            ->addSelect('SUM(c.overallTotal) - sum(c.itemsTotal) as margin_amount')
            ->innerJoin('s.calculations', 'c')
            ->groupBy('s.id')
            ->orderBy('s.code', Criteria::ASC);
    }

    private function getDropDownQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('s')
            ->select('s.id')
            ->addSelect('s.code')
            ->addSelect('s.editable')
            ->innerJoin('s.calculations', 'c')
            ->groupBy('s.id')
            ->orderBy('s.editable', Criteria::DESC)
            ->addOrderBy('s.code', Criteria::ASC);
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
                    'text' => 1 === $key ? 'calculationstate.list.editable' : 'calculationstate.list.not_editable',
                    'states' => [],
                ];
            }
            $result[$key]['states'][$value['code']] = $value['id'];
        }

        return $result;
    }
}
