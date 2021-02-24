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

use App\Doctrine\ColumnHydrator;
use App\Entity\Calculation;
use App\Entity\CalculationState;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for calculation entity.
 *
 * @author Laurent Muller
 *
 * @see \App\Entity\Calculation
 */
class CalculationRepository extends AbstractRepository
{
    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry the connections and entity managers registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Calculation::class);
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
        $result = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->innerJoin('e.state', 's')
            ->where('s.id = :stateId')
            ->setParameter('stateId', $state->getId(), Types::INTEGER)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    /**
     * {@inheritdoc}
     */
    public function createDefaultQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        return parent::createDefaultQueryBuilder($alias)
            ->innerJoin("$alias.state", 's');
    }

    /**
     * Gets all calculations order by identifier descending.
     *
     * @return Calculation[]
     */
    public function findAllById(): array
    {
        return $this->findBy([], ['id' => Criteria::DESC]);
    }

    /**
     * Gets calculations with the overall margin below the given value.
     *
     * @param float $minMargin the minimum margin in percent
     *
     * @return Calculation[] the below calculations
     */
    public function getBelowMargin(float $minMargin): array
    {
        // builder
        $builder = $this->createQueryBuilder('c')
            ->addOrderBy('c.id', Criteria::DESC)
            ->where('c.itemsTotal != 0')
            ->andWhere('(c.overallTotal / c.itemsTotal) < :minMargin ')
            ->setParameter('minMargin', $minMargin, Types::FLOAT);

        // execute
        return $builder->getQuery()->getResult();
    }

    /**
     * Gets calculation by the given date range.
     *
     * @param \DateTimeInterface $from the start date (exclusive)
     * @param \DateTimeInterface $to   the end date (inclusive)
     *
     * @return Calculation[] an array, maybe empty, of calculations
     */
    public function getByInterval(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        $builder = $this->createQueryBuilder('c')
            ->where('c.date > :from')
            ->andWhere('c.date <= :to')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('c.date', Criteria::DESC)
            ->addOrderBy('c.id', Criteria::DESC);

        return $builder->getQuery()->getResult();
    }

    /**
     * Gets calculations grouped by months.
     *
     * @param int $maxResults the maximum number of results to retrieve (the "limit")
     *
     * @return array an array with the year, the month, the number and the sum of calculations
     */
    public function getByMonth(int $maxResults = 6): array
    {
        // build
        $builder = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)               as count')
            ->addSelect('SUM(c.itemsTotal)      as items')
            ->addSelect('SUM(c.overallTotal)    as total')
            ->addSelect('YEAR(c.date)           as year')
            ->addSelect('MONTH(c.date)          as month')
            ->addGroupBy('year')
            ->addGroupBy('month')
            ->orderBy('year', Criteria::DESC)
            ->addOrderBy('month', Criteria::DESC)
            ->setMaxResults($maxResults);

        // execute
        $result = $builder->getQuery()->getArrayResult();

        // create dates
        foreach ($result as &$item) {
            $y = (int) ($item['year']);
            $m = (int) ($item['month']);
            $dt = new \DateTime();
            $dt->setDate($y, $m, 1);
            $item['date'] = $dt;
        }

        //reverse
        return \array_reverse($result);
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

        $result = $builder->getQuery()
            ->getResult(ColumnHydrator::NAME);

        return \array_map('intval', $result);
    }

    /**
     * Gets the distinct years and months of calculations.
     *
     * @return int[] the distinct years and months
     */
    public function getCalendarYearsMonths(): array
    {
        $year = 'year(e.date)';
        $month = 'month(e.date)';

        $builder = $this->createQueryBuilder('e')
            ->select("$year AS year")
            ->addSelect("$month AS month")
            ->distinct()
            ->orderBy($year)
            ->addOrderBy($month);

        $result = $builder->getQuery()
            ->getArrayResult();

        foreach ($result as &$entry) {
            $entry['year'] = (int) ($entry['year']);
            $entry['month'] = (int) ($entry['month']);
            $entry['year_month'] = $entry['year'] * 1000 + $entry['month'];
        }

        return $result;
    }

    /**
     * Gets the distinct years and week of calculations.
     *
     * @return int[] the distinct years and weeks
     */
    public function getCalendarYearsWeeks(): array
    {
        $year = 'year(e.date)';
        $week = 'week(e.date, 3)';

        $builder = $this->createQueryBuilder('e')
            ->select("$year AS year")
            ->addSelect("$week AS week")
            ->distinct()
            ->orderBy($year)
            ->addOrderBy($week);

        $result = $builder->getQuery()
            ->getArrayResult();

        foreach ($result as &$entry) {
            $entry['year'] = (int) ($entry['year']);
            $entry['week'] = (int) ($entry['week']);
            $entry['year_week'] = $entry['year'] * 1000 + $entry['week'];
        }

        return $result;
    }

    /**
     * Find duplicate items in the calculations. Items are duplicate if the descriptions are equal.
     *
     * @param string $orderColumn    the order column
     * @param string $orderDirection the order direction ('ASC' or 'DESC')
     *
     * @return array the array result
     */
    public function getDuplicateItems(string $orderColumn = 'id', string $orderDirection = Criteria::DESC): array
    {
        // build
        $builder = $this->createQueryBuilder('e')
            // calculation
            ->select('e.id              as calculation_id')
            ->addSelect('e.date         as calculation_date')
            ->addSelect('e.customer     as calculation_customer')
            ->addSelect('e.description  as calculation_description')

            // state
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

            ->groupBy('e.id')
            ->addGroupBy('s.code')
            ->addGroupBy('i.description')

            ->having('item_count > 1');

        // order column and direction
        $this->updateOrder($builder, $orderColumn, $orderDirection);

        // execute
        $items = $builder->getQuery()->getArrayResult();

        // map calculations => items
        $result = [];
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
     * @return array the array result
     */
    public function getEmptyItems(string $orderColumn = 'id', string $orderDirection = Criteria::DESC): array
    {
        // build
        $builder = $this->createQueryBuilder('e')
            // calculation
            ->select('e.id              as calculation_id')
            ->addSelect('e.date         as calculation_date')
            ->addSelect('e.customer     as calculation_customer')
            ->addSelect('e.description  as calculation_description')

            // state
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

            ->groupBy('e.id')
            ->addGroupBy('s.code')
            ->addGroupBy('i.description')

            ->having('item_price = 0')
            ->orHaving('item_quantity = 0');

        // order column and direction
        $this->updateOrder($builder, $orderColumn, $orderDirection);

        // execute
        $items = $builder->getQuery()->getArrayResult();

        // map calculations => items
        $result = [];
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
     */
    public function getForWeek(int $year, int $week): array
    {
        $today = new \DateTime('today');
        $start = clone $today->setISODate($year, $week, 1);
        $end = clone $today->setISODate($year, $week, 7);
        if ($start < $end) {
        }

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
     */
    public function getForYear(int $year): array
    {
        return $this->getCalendarBuilder($year)
            ->getQuery()
            ->getResult();
    }

    /**
     * Gets the last calculations.
     *
     * @param int $maxResults the maximum number of results to retrieve (the "limit")
     *
     * @return Calculation[] the last calculations
     */
    public function getLastCalculations(int $maxResults = 12): array
    {
        // builder
        $builder = $this->createQueryBuilder('c')
            ->addOrderBy('c.updatedAt', Criteria::DESC)
            ->addOrderBy('c.date', Criteria::DESC)
            ->addOrderBy('c.id', Criteria::DESC)
            ->setMaxResults($maxResults);

        // execute
        return $builder->getQuery()->getResult();
    }

    /**
     * Gets data for the pivot table.
     */
    public function getPivot(): array
    {
        // build
        $builder = $this->createQueryBuilder('e')
            // calculation
            ->select('e.id                                   AS calculation_id')
            ->addSelect('e.date                              AS calculation_date')
            ->addSelect('(e.overallTotal / e.itemsTotal) - 1 AS calculation_overall_margin')
            ->addSelect('e.overallTotal                      AS calculation_overall_total')
            // state
            ->addSelect('s.code                              AS calculation_state')
            // groups
            ->addSelect('g.code                              AS item_group')
            // category
            ->addSelect('c.code                              AS item_category')
            // items
            ->addSelect('i.description                       AS item_description')
            ->addSelect('i.price                             AS item_price')
            ->addSelect('i.quantity                          AS item_quantity')
            ->addSelect('i.price * i.quantity                AS item_total')

            // tables
            ->innerJoin('e.state', 's')
            ->innerJoin('e.groups', 'g')
            ->innerJoin('g.categories', 'c')
            ->innerJoin('c.items', 'i')

            // not empty
            ->where('e.itemsTotal != 0');

        // execute
        return $builder->getQuery()
            ->getArrayResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchFields(string $field, string $alias = self::DEFAULT_ALIAS)
    {
        switch ($field) {
            case 'date':
                return "DATE_FORMAT({$alias}.{$field}, '%d.%m.%Y')";
            case 'overallMargin':
                return "IFELSE({$alias}.itemsTotal != 0, CEIL(100 * {$alias}.overallTotal / {$alias}.itemsTotal), 0)";
            case 'state.id':
                return 's.id';
            case 'state.code':
                return 's.code';
            case 'state.color':
                return 's.color';
            default:
                return parent::getSearchFields($field, $alias);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSortFields(string $field, string $alias = self::DEFAULT_ALIAS)
    {
        switch ($field) {
            case 'overallMargin':
                return "IFELSE({$alias}.itemsTotal != 0, {$alias}.overallTotal / {$alias}.itemsTotal, 0)";
            case 'state.id':
            case 'state.code':
                return 's.code';
            case 'state.color':
                return 's.color';
            default:
                return parent::getSortFields($field, $alias);
        }
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
            ->addOrderBy('c.id', Criteria::DESC)
            ->where('YEAR(c.date) = :year')
            ->setParameter('year', $year, Types::INTEGER);
    }

    /**
     * Gets the sort direction.
     *
     * @param string $direction the direction to validate
     * @param string $default   the default direction
     *
     * @return string the sort direction
     */
    private function getDirection(string $direction, string $default): string
    {
        $direction = \strtoupper($direction);
        switch ($direction) {
            case Criteria::ASC:
            case Criteria::DESC:
                return $direction;
            default:
                return $default;
        }
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
        switch ($column) {
            case 'id':
            case 'date':
            case 'customer':
            case 'description':
                return "e.$column";
            case 'state':
                return 's.code';
            default:
                return 'e.id';
        }
    }

    /**
     * Update the order by of the given query builder.
     *
     * @param QueryBuilder $builder        the query builder to update
     * @param string       $orderColumn    the order column
     * @param string       $orderDirection the order direction ('ASC' or 'DESC')
     */
    private function updateOrder(QueryBuilder $builder, string $orderColumn, string $orderDirection): void
    {
        $orderColumn = $this->getOrder($orderColumn);
        $orderDirection = $this->getDirection($orderDirection, Criteria::DESC);
        $builder->orderBy($orderColumn, $orderDirection);
    }

    /**
     * Update the given result.
     *
     * @param array $result the result to update
     * @param array $item   the item to get values for creating an entry result
     * @param array $values the values to add as an item entry
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
                'stateCode' => $item['calculation_state'],
                'stateColor' => $item['calculation_color'],
                'stateEditable' => $item['calculation_editable'],
                'items' => [],
            ];
        }

        $result[$key]['items'][] = $values;
    }
}
