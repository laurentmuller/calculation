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

use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Model\CalculationsMonth;
use App\Model\CalculationsMonthItem;
use App\Utils\DateUtils;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\DatePointType;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Repository for calculation entity.
 *
 * @extends AbstractRepository<Calculation>
 *
 * @phpstan-type CalculationItemEntry = array{
 *        description: string,
 *        quantity: float,
 *        price: float,
 *        count: int}
 * @phpstan-type CalculationItemType = array{
 *        id: int,
 *        date: DatePoint,
 *        stateCode: string,
 *        customer: string,
 *        description: string,
 *        items: CalculationItemEntry[]}
 * @phpstan-type PivotType = array{
 *        calculation_id: int,
 *        calculation_date: DatePoint,
 *        calculation_overall_margin: float,
 *        calculation_overall_total: float,
 *        calculation_state: string,
 *        item_group: string,
 *        item_category: string,
 *        item_description: string,
 *        item_price: float,
 *        item_quantity: float,
 *        item_total: float,
 *        item_overall: float}
 */
class CalculationRepository extends AbstractRepository
{
    /**
     * The alias for the state entity.
     */
    final public const STATE_ALIAS = 's';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Calculation::class);
    }

    /**
     * Update the given query builder by adding the filter for calculations below the given margin.
     *
     * @param QueryBuilder $builder   the query builder to update
     * @param float        $minMargin the minimum margin
     * @param ?string      $alias     the entity alias to use or null to use the first root alias
     *
     * @return QueryBuilder the updated query builder
     */
    public static function addBelowFilter(QueryBuilder $builder, float $minMargin, ?string $alias = null): QueryBuilder
    {
        $param = 'minMargin';
        $alias ??= $builder->getRootAliases()[0];
        $itemsField = $alias . '.itemsTotal';
        $overallField = $alias . '.overallTotal';

        return $builder->andWhere($itemsField . ' != 0')
            ->andWhere(\sprintf('(%s / %s) < :%s', $overallField, $itemsField, $param))
            ->setParameter($param, $minMargin, Types::FLOAT);
    }

    /**
     * Returns the number of distinct years and months.
     */
    public function countDistinctMonths(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select("COUNT (DISTINCT DATE_FORMAT(c.date, '%Y-%m'))")
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Gets the number of calculations below the given margin.
     *
     * @param float $minMargin the minimum margin
     *
     * @return int<0, max>
     */
    public function countItemsBelow(float $minMargin): int
    {
        $builder = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)');

        /** @phpstan-var int<0, max> */
        return (int) self::addBelowFilter($builder, $minMargin)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count the number of calculations with duplicate items.
     *
     * Items are duplicate if the descriptions are equal.
     *
     * @return int<0, max>
     */
    public function countItemsDuplicate(): int
    {
        $dql = $this->createQueryBuilder('e')
            ->select('e.id')
            ->innerJoin('e.groups', 'g')
            ->innerJoin('g.categories', 'c')
            ->innerJoin('c.items', 'i')
            ->groupBy('e.id', 'i.description')
            ->having('COUNT(i.id) > 1')
            ->getDQL();
        $where = \sprintf('r.id in(%s)', $dql);

        /** @phpstan-var int<0, max> */
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where($where)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count the number of calculations with empty items.
     *
     * Items are empty if the price or the quantity is equal to 0.
     *
     * @return int<0, max>
     */
    public function countItemsEmpty(): int
    {
        $dql = $this->createQueryBuilder('e')
            ->select('e.id')
            ->innerJoin('e.groups', 'g')
            ->innerJoin('g.categories', 'c')
            ->innerJoin('c.items', 'i')
            ->where('i.price = 0')
            ->orWhere('i.quantity = 0')
            ->getDQL();
        $where = \sprintf('r.id in(%s)', $dql);

        /** @phpstan-var int<0, max> */
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where($where)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count the number of calculations for the given state.
     *
     * @param CalculationState $state the state to search for
     *
     * @return int the number of calculations
     */
    public function countStateReferences(CalculationState $state): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.state= :state')
            ->setParameter('state', $state->getId(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult();
    }

    #[\Override]
    public function createDefaultQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        return parent::createDefaultQueryBuilder($alias)
            ->innerJoin($alias . '.state', self::STATE_ALIAS)
            ->addSelect(self::STATE_ALIAS);
    }

    /**
     * Gets calculation for the given date range.
     *
     * @param DatePoint $from the start date (exclusive)
     * @param DatePoint $to   the end date (inclusive)
     *
     * @return Calculation[] an array, maybe empty, of calculations
     */
    public function getByInterval(DatePoint $from, DatePoint $to): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.date > :from')
            ->andWhere('c.date <= :to')
            ->setParameter('from', $from, DatePointType::NAME)
            ->setParameter('to', $to, DatePointType::NAME)
            ->orderBy('c.date', self::SORT_DESC)
            ->addOrderBy('c.id', self::SORT_DESC)
            ->getQuery()
            ->getResult();
    }

    /**
     * Gets the calculations grouped by years and months sorted by date in descending order.
     *
     * @param int $maxResults the maximum number of results to retrieve (the "limit")
     */
    public function getByMonth(int $maxResults = 6): CalculationsMonth
    {
        $builder = $this->createQueryBuilder('c')
            ->select('COUNT(c.id) as count')
            ->addSelect('ROUND(SUM(c.itemsTotal), 2) as items')
            ->addSelect('ROUND(SUM(c.overallTotal), 2) as total')
            ->addSelect('YEAR(c.date) as year')
            ->addSelect('MONTH(c.date) as month')
            ->groupBy('year', 'month')
            ->orderBy('year', self::SORT_DESC)
            ->addOrderBy('month', self::SORT_DESC)
            ->setMaxResults($maxResults);

        $result = \array_reverse($builder->getQuery()->getArrayResult());
        $items = \array_map(static fn (array $row): CalculationsMonthItem => new CalculationsMonthItem(
            $row['count'],
            $row['items'],
            $row['total'],
            $row['year'],
            $row['month']
        ), $result);

        return new CalculationsMonth($items);
    }

    /**
     * Gets the distinct years of calculations.
     *
     * @return int[] the distinct years
     */
    public function getCalendarYears(): array
    {
        $year = 'year(e.date)';
        $builder = $this->createQueryBuilder('e')
            ->select($year)
            ->distinct()
            ->orderBy($year);

        return $builder->getQuery()->getSingleColumnResult();
    }

    /**
     * Gets the distinct years and months of calculations.
     *
     * @return array<int[]> the distinct years and months
     *
     * @phpstan-return array<array{
     *      year: int,
     *      month: int,
     *      year_month: int}>
     */
    public function getCalendarYearsMonths(): array
    {
        $year = 'year(e.date)';
        $month = 'month(e.date)';
        $year_month = \sprintf('%s * 1000 + %s', $year, $month);
        $builder = $this->createQueryBuilder('e')
            ->select($year . ' AS year')
            ->addSelect($month . ' AS month')
            ->addSelect($year_month . ' AS year_month')
            ->distinct()
            ->orderBy($year)
            ->addOrderBy($month);

        return $builder->getQuery()->getArrayResult();
    }

    /**
     * Gets the distinct years and week of calculations.
     *
     * @return int[] the distinct years and weeks
     *
     * @phpstan-return array<array{
     *      year: int,
     *      month: int,
     *      year_week: int}>
     */
    public function getCalendarYearsWeeks(): array
    {
        $year = 'year(e.date)';
        $week = 'week(e.date, 3)';
        $year_week = \sprintf('%s * 1000 + %s', $year, $week);
        $builder = $this->createQueryBuilder('e')
            ->select($year . ' AS year')
            ->addSelect($week . ' AS week')
            ->addSelect($year_week . ' AS year_week')
            ->distinct()
            ->orderBy($year)
            ->addOrderBy($week);

        return $builder->getQuery()->getArrayResult();
    }

    /**
     * Gets calculations for the given year and month.
     *
     * @param int $year  the year
     * @param int $month the month number (1 = January, 2 = February, ...)
     *
     * @return Calculation[] the matching calculations
     */
    public function getForMonth(int $year, int $month): array
    {
        return $this->getCalendarBuilder($year)
            ->andWhere('MONTH(c.date) = :month')
            ->setParameter('month', $month, Types::INTEGER)
            ->getQuery()
            ->getResult();
    }

    /**
     * Gets calculations for the given year and week.
     *
     * @param int $year the year
     * @param int $week the week number (1 to 53)
     *
     * @return Calculation[] the matching calculations
     *
     * @phpstan-return list<Calculation>
     */
    public function getForWeek(int $year, int $week): array
    {
        return $this->getCalendarBuilder($year)
            ->andWhere('WEEK(c.date, 3) = :week')
            ->setParameter('week', $week, Types::INTEGER)
            ->getQuery()
            ->getResult();
    }

    /**
     * Gets calculations for the given year.
     *
     * @param int $year the year
     *
     * @return Calculation[] the matching calculations
     *
     * @phpstan-return list<Calculation>
     */
    public function getForYear(int $year): array
    {
        return $this->getCalendarBuilder($year)
            ->getQuery()
            ->getResult();
    }

    /**
     * Gets calculations with the overall margin below the given value.
     *
     * @param float $minMargin the minimum margin in percent
     *
     * @return Calculation[] the below calculations
     */
    public function getItemsBelow(float $minMargin): array
    {
        $builder = $this->createQueryBuilder('c')
            ->addOrderBy('c.id', self::SORT_DESC);

        return self::addBelowFilter($builder, $minMargin)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find duplicate items in the calculations. Items are duplicate if the descriptions are equal.
     *
     * @param string $orderColumn    the order column
     * @param string $orderDirection the order direction ('ASC' or 'DESC')
     *
     * @phpstan-param self::SORT_* $orderDirection
     *
     * @phpstan-return CalculationItemType[]
     */
    public function getItemsDuplicate(string $orderColumn = 'id', string $orderDirection = self::SORT_DESC): array
    {
        $builder = $this->createQueryBuilder('e')
            // calculation
            ->select('e.id              as calculation_id')
            ->addSelect('e.date         as calculation_date')
            ->addSelect('e.customer     as calculation_customer')
            ->addSelect('e.description  as calculation_description')

            // state
            ->addSelect('s.id           as calculation_state_id')
            ->addSelect('s.code         as calculation_state')
            ->addSelect('s.color        as calculation_color')
            ->addSelect('s.editable     as calculation_editable')

            // item
            ->addSelect('i.description  as item_description')
            ->addSelect('count(i.id)    as item_count')

            ->innerJoin('e.state', 's')
            ->innerJoin('e.groups', 'g')
            ->innerJoin('g.categories', 'c')
            ->innerJoin('c.items', 'i')

            ->groupBy('e.id', 's.code', 'i.description')

            ->having('item_count > 1');
        $this->updateOrder($builder, $orderColumn, $orderDirection);
        $items = $builder->getQuery()->getArrayResult();

        /** @phpstan-var CalculationItemType[] $result */
        $result = [];

        /** @phpstan-var array{
         *      calculation_id: int,
         *      calculation_date: DatePoint,
         *      calculation_customer: string,
         *      calculation_description: string,
         *      calculation_state_id: int,
         *      calculation_state: string,
         *      calculation_color: string,
         *      calculation_editable: bool,
         *      item_description: string,
         *      item_count: int
         * } $item */
        foreach ($items as $item) {
            $this->updateResult($result, $item, [
                'description' => $item['item_description'],
                'count' => $item['item_count'],
            ]);
        }

        return $result;
    }

    /**
     * Find empty items in the calculations. Items are empty if the price or the quantity is equal to 0.
     *
     * @param string $orderColumn    the order column
     * @param string $orderDirection the order direction ('ASC' or 'DESC')
     *
     * @phpstan-param self::SORT_* $orderDirection
     *
     * @phpstan-return CalculationItemType[]
     */
    public function getItemsEmpty(string $orderColumn = 'id', string $orderDirection = self::SORT_DESC): array
    {
        $builder = $this->createQueryBuilder('e')
            // calculation
            ->select('e.id              as calculation_id')
            ->addSelect('e.date         as calculation_date')
            ->addSelect('e.customer     as calculation_customer')
            ->addSelect('e.description  as calculation_description')

            // state
            ->addSelect('s.id           as calculation_state_id')
            ->addSelect('s.code         as calculation_state')
            ->addSelect('s.color        as calculation_color')
            ->addSelect('s.editable     as calculation_editable')

            // item
            ->addSelect('i.description  as item_description')
            ->addSelect('i.price        as item_price')
            ->addSelect('i.quantity     as item_quantity')

            ->innerJoin('e.state', 's')
            ->innerJoin('e.groups', 'g')
            ->innerJoin('g.categories', 'c')
            ->innerJoin('c.items', 'i')

            ->groupBy('e.id', 's.code', 'i.description')

            ->having('item_price = 0')
            ->orHaving('item_quantity = 0');
        $this->updateOrder($builder, $orderColumn, $orderDirection);
        $items = $builder->getQuery()->getArrayResult();

        /** @phpstan-var CalculationItemType[] $result */
        $result = [];

        /** @phpstan-var array{
         *      calculation_id: int,
         *      calculation_date: DatePoint,
         *      calculation_customer: string,
         *      calculation_description: string,
         *      calculation_state_id: int,
         *      calculation_state: string,
         *      calculation_color: string,
         *      calculation_editable: bool,
         *      item_description: string,
         *      item_count: int,
         *      item_description: string,
         *      item_quantity: float,
         *      item_price: float,
         * } $item */
        foreach ($items as $item) {
            $this->updateResult($result, $item, [
                'description' => $item['item_description'],
                'quantity' => $item['item_quantity'],
                'price' => $item['item_price'],
            ]);
        }

        return $result;
    }

    /**
     * Gets the last calculations.
     *
     * @param int            $maxResults the maximum number of calculations to retrieve (the "limit")
     * @param ?UserInterface $user       if not null, returns the user's last calculations
     */
    public function getLastCalculations(int $maxResults, ?UserInterface $user = null): array
    {
        $builder = $this->getTableQueryBuilder();
        $builder->addOrderBy('e.updatedAt', self::SORT_DESC)
            ->addOrderBy('e.date', self::SORT_DESC)
            ->addOrderBy('e.id', self::SORT_DESC)
            ->setMaxResults($maxResults);
        if ($user instanceof UserInterface) {
            $identifier = $user->getUserIdentifier();
            $builder->where('e.createdBy = :identifier')
                ->orWhere('e.updatedBy = :identifier')
                ->setParameter('identifier', $identifier, Types::STRING);
        }

        return $builder->getQuery()->getArrayResult();
    }

    /**
     * Gets the minimum (first) and maximum (last) dates of calculations.
     *
     * @phpstan-return array{0: ?DatePoint, 1: ?DatePoint}
     */
    public function getMinMaxDates(): array
    {
        /** @phpstan-var array{MIN_DATE: ?string, MAX_DATE: ?string} $values */
        $values = $this->createQueryBuilder('c')
            ->select('MIN(c.date) as MIN_DATE')
            ->addSelect('MAX(c.date) as MAX_DATE')
            ->getQuery()
            ->getSingleResult();
        if (null === $values['MIN_DATE'] || null === $values['MAX_DATE']) {
            return [null, null];
        }

        return [
            DateUtils::createDatePoint($values['MIN_DATE']),
            DateUtils::createDatePoint($values['MAX_DATE']),
        ];
    }

    /**
     * Gets data for the pivot table.
     *
     * @phpstan-return PivotType[]
     */
    public function getPivot(): array
    {
        $builder = $this->createQueryBuilder('e')
            // calculation
            ->select('e.id                                   AS calculation_id')
            ->addSelect('e.date                              AS calculation_date')
            ->addSelect('(e.overallTotal / e.itemsTotal) - 1 AS calculation_overall_margin')
            ->addSelect('e.overallTotal                      AS calculation_overall_total')
            // state
            ->addSelect('s.code                              AS calculation_state')
            // groupes
            ->addSelect('g.code                              AS item_group')
            // category
            ->addSelect('c.code                              AS item_category')
            // items
            ->addSelect('i.description                       AS item_description')
            ->addSelect('i.price                             AS item_price')
            ->addSelect('i.quantity                          AS item_quantity')
            ->addSelect('i.price * i.quantity                AS item_total')
            ->addSelect('i.price * i.quantity * g.margin     AS item_overall')

            // tables
            ->innerJoin('e.state', 's')
            ->innerJoin('e.groups', 'g')
            ->innerJoin('g.categories', 'c')
            ->innerJoin('c.items', 'i')

            // not empty
            ->where('i.quantity != 0')
            ->andWhere('i.price != 0');

        return $builder->getQuery()->getArrayResult();
    }

    #[\Override]
    public function getSearchFields(string $field, string $alias = self::DEFAULT_ALIAS): array|string
    {
        return match ($field) {
            'date' => \sprintf("DATE_FORMAT(%s.%s, '%%d.%%m.%%Y')", $alias, $field),
            'overallMargin' => $this->getOverallMargin($alias),
            'state.id' => parent::getSearchFields('id', self::STATE_ALIAS),
            'stateCode',
            'state.code' => parent::getSearchFields('code', self::STATE_ALIAS),
            'stateColor',
            'state.color' => parent::getSearchFields('color', self::STATE_ALIAS),
            'stateEditable',
            'state.editable' => parent::getSearchFields('editable', self::STATE_ALIAS),
            default => parent::getSearchFields($field, $alias),
        };
    }

    #[\Override]
    public function getSortField(string $field, string $alias = self::DEFAULT_ALIAS): string
    {
        return match ($field) {
            'overallMargin' => $this->getOverallMargin($alias),
            'stateId',
            'state_id',
            'state.id',
            'code',
            'stateCode',
            'state_code',
            'state.code' => parent::getSortField('code', self::STATE_ALIAS),
            'color',
            'stateColor',
            'state_color',
            'state.color' => parent::getSortField('color', self::STATE_ALIAS),
            'editable',
            'stateEditable',
            'state_editable',
            'state.editable' => parent::getSortField('editable', self::STATE_ALIAS),
            default => parent::getSortField($field, $alias),
        };
    }

    /**
     * Gets the query builder for the table.
     *
     * @param string $alias the entity alias
     */
    public function getTableQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->select($alias . '.id')
            ->addSelect($alias . '.date')
            ->addSelect($alias . '.customer')
            ->addSelect($alias . '.description')
            ->addSelect($alias . '.overallTotal')
            ->addSelect($this->getOverallMargin($alias, 100) . ' as overallMargin')
            ->addSelect(self::STATE_ALIAS . '.id as stateId')
            ->addSelect(self::STATE_ALIAS . '.code as stateCode')
            ->addSelect(self::STATE_ALIAS . '.color as stateColor')
            ->addSelect(self::STATE_ALIAS . '.editable as stateEditable')
            ->innerJoin($alias . '.state', self::STATE_ALIAS)
            ->groupBy($alias . '.id');
    }

    /**
     * Gets the basic query builder to search calculations for a given year.
     *
     * @param int $year the year to select
     *
     * @return QueryBuilder the query builder
     */
    private function getCalendarBuilder(int $year): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.date')
            ->addOrderBy('c.id', self::SORT_DESC)
            ->where('YEAR(c.date) = :year')
            ->setParameter('year', $year, Types::INTEGER);
    }

    /**
     * Gets the full order column name.
     *
     * @param string $column the order column to validate
     *
     * @return string the full order column name
     */
    private function getOrder(string $column): string
    {
        return match ($column) {
            'id',
            'date',
            'customer',
            'description' => 'e.' . $column,
            'stateCode' => 's.code',
            default => 'e.id',
        };
    }

    private function getOverallMargin(string $alias, float $divide = 1.0): string
    {
        return \sprintf(
            'IFELSE(%1$s.itemsTotal != 0, ROUND((100 * %1$s.overallTotal / %1$s.itemsTotal) - 0.5, 0) / %2$s, 0)',
            $alias,
            $divide
        );
    }

    /**
     * Update the order for the given query builder.
     *
     * @param QueryBuilder $builder        the query builder to update
     * @param string       $orderColumn    the order column
     * @param string       $orderDirection the order direction ('ASC' or 'DESC')
     */
    private function updateOrder(QueryBuilder $builder, string $orderColumn, string $orderDirection): void
    {
        $orderColumn = $this->getOrder($orderColumn);
        $builder->orderBy($orderColumn, $orderDirection);
    }

    /**
     * Update the given result.
     *
     * @param array $result the result to update
     * @param array $item   the item to get values for creating a new entry result
     * @param array $values the values to add as an item entry
     *
     * @phpstan-param array{
     *      calculation_id: int,
     *      calculation_date: DatePoint,
     *      calculation_customer: string,
     *      calculation_description: string,
     *      calculation_state_id: int,
     *      calculation_state: string,
     *      calculation_color: string,
     *      calculation_editable: bool
     * } $item
     */
    private function updateResult(array &$result, array $item, array $values): void
    {
        $key = $item['calculation_id'];
        if (!\array_key_exists($key, $result)) {
            $result[$key] = [
                'id' => $key,
                'date' => $item['calculation_date'],
                'customer' => $item['calculation_customer'],
                'description' => $item['calculation_description'],
                'stateId' => $item['calculation_state_id'],
                'stateCode' => $item['calculation_state'],
                'stateColor' => $item['calculation_color'],
                'stateEditable' => $item['calculation_editable'],
                'items' => [],
            ];
        }

        $result[$key]['items'][] = $values;
    }
}
