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
use App\Model\CalculationsState;
use App\Model\CalculationsStateItem;
use App\Traits\ArrayTrait;
use App\Traits\GroupByTrait;
use App\Traits\MathTrait;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for calculation state entity.
 *
 * @phpstan-type DropDownType = array<int, array{
 *     id: int,
 *     icon: string,
 *     text: string,
 *     states: array<string, int>}>
 *
 * @extends AbstractRepository<CalculationState>
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
     */
    public function getCalculations(): CalculationsState
    {
        $builder = $this->createQueryBuilder('s')
            ->select(\sprintf(
                'NEW %s(
                    s.id,
                    s.code,
                    s.editable,
                    s.color,
                    COUNT(c.id),
                    ROUND(SUM(c.itemsTotal), 2),
                    ROUND(SUM(c.overallTotal), 2)
                )',
                CalculationsStateItem::class
            ))
            ->innerJoin('s.calculations', 'c')
            ->groupBy('s.id')
            ->orderBy('s.code', self::SORT_ASC);

        /** @var CalculationsStateItem[] $items */
        $items = $builder->getQuery()
            ->getResult();

        return new CalculationsState($items);
    }

    /**
     * Gets states used for the calculation table.
     *
     * <b>Note:</b> Only states with at least one calculation are returned.
     *
     * @return array an array grouped by editable with the states
     *
     * @phpstan-return DropDownType
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
     * @phpstan-return DropDownType
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
     */
    public function getEditableCount(): int
    {
        return $this->countStates(true);
    }

    /**
     * Gets query builder for the state where editable is true.
     *
     * @param literal-string $alias the entity alias
     */
    public function getEditableQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->getSortedBuilder($alias)
            ->addCriteria($this->getEditableCriteria(true));
    }

    /**
     * Get the number of calculation states where editable is false.
     */
    public function getNotEditableCount(): int
    {
        return $this->countStates(false);
    }

    /**
     * Gets query builder for the state where editable is false.
     *
     * @param literal-string $alias the entity alias
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
        return Criteria::create(true)
            ->where(Criteria::expr()->eq('editable', $editable));
    }

    /**
     * @phpstan-return DropDownType
     */
    private function mergeDropDown(QueryBuilder $builder): array
    {
        /**
         * @phpstan-var array<array{
         *     id: int,
         *     code: string,
         *     editable: bool}> $values
         */
        $values = $builder->getQuery()->getArrayResult();
        if (\count($values) <= 1) {
            return [];
        }

        /** @phpstan-var DropDownType $result */
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
