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
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Repository for calculation entity.
 *
 * @template-extends AbstractRepository<Calculation>
 *
 * @psalm-suppress  MixedReturnTypeCoercion
 */
class CalculationRepository extends AbstractRepository
{
    /**
     * The alias for the state entity.
     */
    final public const STATE_ALIAS = 's';

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
     * Update the given query builder by adding the filter for calculations below the given margin.
     *
     * @param QueryBuilder $builder   the query builder to update
     * @param float        $minMargin the minimum margin
     *
     * @return QueryBuilder the updated query builder
     */
    public function addBelowFilter(QueryBuilder $builder, float $minMargin): QueryBuilder
    {
        $param = 'minMargin';
        $alias = $builder->getRootAliases()[0];
        $itemsField = "$alias.itemsTotal";
        $overallField = "$alias.overallTotal";

        return $builder
            ->andWhere("$itemsField != 0")
            ->andWhere("($overallField / $itemsField) < :$param")
            ->setParameter($param, $minMargin, Types::FLOAT);
    }

    /**
     * Gets the number of calculations below the given margin.
     *
     * @param float $minMargin the minimum margin
     *
     * @return int the number of calculations
     *
     * @psalm-return  int<0, max>
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function countBelowItems(float $minMargin): int
    {
        // create
        $builder = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)');
        $builder = $this->addBelowFilter($builder, $minMargin);
        /** @var int<0, max> $count */
        $count = (int) $builder->getQuery()->getSingleScalarResult();

        return $count;
    }

    /**
     * Returns the number of distinct years and months.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function countDistinctMonths(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select("COUNT (DISTINCT DATE_FORMAT(c.date, '%Y-%m'))")
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count the number of calculations with duplicate items. Items are duplicate if the descriptions are equal.
     *
     * @return int the number of calculations
     *
     * @psalm-return  int<0, max>
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function countDuplicateItems(): int
    {
        // sub query
        $dql = $this->createQueryBuilder('e')
            ->select('e.id')
            ->innerJoin('e.groups', 'g')
            ->innerJoin('g.categories', 'c')
            ->innerJoin('c.items', 'i')
            ->groupBy('e.id')
            ->addGroupBy('i.description')
            ->having('COUNT(i.id) > 1')
            ->getDQL();

        // main query
        /** @psalm-var literal-string $where */
        $where = "r.id in($dql)";
        $builder = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where($where);

        /** @phpstan-var int<0, max> $count */
        $count = (int) $builder->getQuery()->getSingleScalarResult();

        return $count;
    }

    /**
     * Count the number of calculations with empty items. Items are empty if the price or the quantity is equal to 0.
     *
     * @return int the number of calculations
     *
     * @psalm-return int<0, max>
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function countEmptyItems(): int
    {
        // sub query
        $dql = $this->createQueryBuilder('e')
            ->select('e.id')
            ->innerJoin('e.groups', 'g')
            ->innerJoin('g.categories', 'c')
            ->innerJoin('c.items', 'i')
            ->where('i.price = 0')
            ->orWhere('i.quantity = 0')
            ->getDQL();

        // main query
        /** @psalm-var literal-string $where */
        $where = "r.id in($dql)";
        $builder = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where($where);

        /** @phpstan-var int<0, max> $count */
        $count = (int) $builder->getQuery()->getSingleScalarResult();

        return $count;
    }

    /**
     * Count the number of calculations for the given state.
     *
     * @param CalculationState $state the state to search for
     *
     * @return int the number of calculations
     *
     * @throws \Doctrine\ORM\Exception\ORMException
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

    /**
     * {@inheritdoc}
     */
    public function createDefaultQueryBuilder(string $alias = self::DEFAULT_ALIAS): QueryBuilder
    {
        return parent::createDefaultQueryBuilder($alias)
            ->innerJoin("$alias.state", self::STATE_ALIAS)
            ->addSelect(self::STATE_ALIAS);
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
        // create
        $builder = $this->createQueryBuilder('c')
            ->addOrderBy('c.id', Criteria::DESC);

        // filter
        $builder = $this->addBelowFilter($builder, $minMargin);

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
        return $this->createQueryBuilder('c')
            ->where('c.date > :from')
            ->andWhere('c.date <= :to')
            ->setParameter('from', $from, Types::DATETIME_MUTABLE)
            ->setParameter('to', $to, Types::DATETIME_MUTABLE)
            ->orderBy('c.date', Criteria::DESC)
            ->addOrderBy('c.id', Criteria::DESC)
            ->getQuery()
            ->getResult();
    }

    /**
     * Gets calculations grouped by months.
     *
     * @param int $maxResults the maximum number of results to retrieve (the "limit")
     *
     * @return array an array with the year, the month, the number and the sum of calculations
     *
     * @psalm-return array<array{
     *      count: int,
     *      items: float,
     *      total: float,
     *      year: int,
     *      month: int,
     *      margin: float,
     *      date: \DateTimeInterface}>
     *
     * @throws \Exception
     */
    public function getByMonth(int $maxResults = 6): array
    {
        // build
        $builder = $this->createQueryBuilder('c')
            ->select('COUNT(c.id) as count')
            ->addSelect('SUM(c.itemsTotal) as items')
            ->addSelect('SUM(c.overallTotal) as total')
            ->addSelect('YEAR(c.date) as year')
            ->addSelect('MONTH(c.date) as month')
            ->addSelect('SUM(c.overallTotal) / SUM(c.itemsTotal) as margin')
            ->groupBy('year')
            ->addGroupBy('month')
            ->orderBy('year', Criteria::DESC)
            ->addOrderBy('month', Criteria::DESC)
            ->setMaxResults($maxResults);

        // execute
        $result = $builder->getQuery()->getArrayResult();

        // convert and create dates
        /** @psalm-var array $item */
        foreach ($result as &$item) {
            $item['year'] = (int) $item['year'];
            $item['month'] = (int) $item['month'];
            $item['date'] = $this->convertToDate($item);
            $item['items'] = (float) $item['items'];
            $item['total'] = (float) $item['total'];
            $item['margin'] = (float) $item['margin'];
        }

        // reverse
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

        $result = $builder->getQuery()->getSingleColumnResult();

        return \array_map('intval', $result);
    }

    /**
     * Gets the distinct years and months of calculations.
     *
     * @return array<int[]> the distinct years and months
     *
     * @psalm-return array<array{
     *      year: int,
     *      month: int,
     *      year_month: int}>
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

        $result = $builder->getQuery()->getArrayResult();

        // convert
        /** @psalm-var array $entry */
        foreach ($result as &$entry) {
            $entry['year'] = (int) $entry['year'];
            $entry['month'] = (int) $entry['month'];
            $entry['year_month'] = $entry['year'] * 1000 + $entry['month'];
        }

        return $result;
    }

    /**
     * Gets the distinct years and week of calculations.
     *
     * @return int[] the distinct years and weeks
     *
     * @psalm-return array<array{
     *      year: int,
     *      month: int,
     *      year_week: int}>
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

        $result = $builder->getQuery()->getArrayResult();

        // convert
        /** @psalm-var array $entry */
        foreach ($result as &$entry) {
            $entry['year'] = (int) $entry['year'];
            $entry['week'] = (int) $entry['week'];
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
     * @psalm-return array<int, array{
     *      id: int,
     *      date: \DateTimeInterface,
     *      stateCode: string,
     *      customer: string,
     *      description: string,
     *      items: array<array{
     *          description: string,
     *          quantity: float,
     *          price: float,
     *          count: int}>
     *      }>
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
        /**
         * @psalm-var array<int, array{
         *      id: int,
         *      date: \DateTimeInterface,
         *      stateCode: string,
         *      customer: string,
         *      description: string,
         *      items: array<array{
         *          description: string,
         *          quantity: float,
         *          price: float,
         *          count: int}>
         *      }> $result
         */
        $result = [];
        /** @psalm-var array{
         *      calculation_id: int,
         *      calculation_date: \DateTimeInterface,
         *      calculation_customer: string,
         *      calculation_description: string,
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
     * @psalm-return array<int, array{
     *      id: int,
     *      date: \DateTimeInterface,
     *      stateCode: string,
     *      customer: string,
     *      description: string,
     *      items: array<array{
     *          description: string,
     *          quantity: float,
     *          price: float,
     *          count: int}>
     *      }>
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
        /**
         * @var array<int, array{
         *      id: int,
         *      date: \DateTimeInterface,
         *      stateCode: string,
         *      customer: string,
         *      description: string,
         *      items: array<array{
         *          description: string,
         *          quantity: float,
         *          price: float,
         *          count: int}>
         *      }> $result
         */
        $result = [];
        /** @psalm-var array{
         *      calculation_id: int,
         *      calculation_date: \DateTimeInterface,
         *      calculation_customer: string,
         *      calculation_description: string,
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
     * @psalm-return list<Calculation>
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
     * @psalm-return list<Calculation>
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
     * @return Calculation[] the last calculations
     *
     * @psalm-return list<Calculation>
     */
    public function getLastCalculations(int $maxResults, ?UserInterface $user = null): array
    {
        // builder
        $builder = $this->createQueryBuilder('c')
            ->addOrderBy('c.updatedAt', Criteria::DESC)
            ->addOrderBy('c.date', Criteria::DESC)
            ->addOrderBy('c.id', Criteria::DESC)
            ->setMaxResults($maxResults);

        // user?
        if (null !== $user) {
            $identifier = $user->getUserIdentifier();
            $builder->where('c.createdBy = :identifier')
                ->orWhere('c.updatedBy = :identifier')
                ->setParameter('identifier', $identifier, Types::STRING);
        }

        // execute
        return $builder->getQuery()->getResult();
    }

    /**
     * Gets tne maximum date of the calculations.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Exception
     */
    public function getMaxDate(): ?\DateTimeInterface
    {
        /** @psalm-var string|null $value */
        $value = $this->createQueryBuilder('c')
            ->select('MAX(c.date) as MAX_DATE')
            ->getQuery()
            ->getSingleScalarResult();

        return \is_string($value) ? new \DateTime($value) : null;
    }

    /**
     * Gets tne minimum date of the calculations.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Exception
     */
    public function getMinDate(): ?\DateTimeInterface
    {
        /** @psalm-var string|null $value */
        $value = $this->createQueryBuilder('c')
            ->select('MIN(c.date) as MIN_DATE')
            ->getQuery()
            ->getSingleScalarResult();

        return \is_string($value) ? new \DateTime($value) : null;
    }

    /**
     * Gets data for the pivot table.
     *
     * @psalm-return array<array{
     *      calculation_id: int,
     *      calculation_date: \DateTimeInterface,
     *      calculation_overall_margin: float,
     *      calculation_overall_total: float,
     *      calculation_state: string,
     *      item_group: string,
     *      item_category: string,
     *      item_description: string,
     *      item_price: float,
     *      item_quantity: float,
     *      item_total: float}>
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
            // groupes
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
        return $builder->getQuery()->getArrayResult();
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchFields(string $field, string $alias = self::DEFAULT_ALIAS): array|string
    {
        return match ($field) {
            'date' => "DATE_FORMAT($alias.$field, '%d.%m.%Y')",
            'overallMargin' => "IFELSE($alias.itemsTotal != 0, FLOOR(100 * $alias.overallTotal / $alias.itemsTotal), 0)",
            'state.id' => parent::getSearchFields('id', self::STATE_ALIAS),
            'state.code' => parent::getSearchFields('code', self::STATE_ALIAS),
            'state.color' => parent::getSearchFields('color', self::STATE_ALIAS),
            default => parent::getSearchFields($field, $alias),
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getSortField(string $field, string $alias = self::DEFAULT_ALIAS): string
    {
        return match ($field) {
            'overallMargin' => "IFELSE($alias.itemsTotal != 0, $alias.overallTotal / $alias.itemsTotal, 0)",
            'state.id',
            'state.code' => parent::getSortField('code', self::STATE_ALIAS),
            'state.color' => parent::getSortField('color', self::STATE_ALIAS),
            default => parent::getSortField($field, $alias),
        };
    }

    /**
     * @throws \Exception
     */
    private function convertToDate(array $item): \DateTimeInterface
    {
        $year = (int) $item['year'];
        $month = (int) $item['month'];
        $time = "$year-$month-10";

        return new \DateTime($time);
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
            'description' => "e.$column",
            'state' => 's.code',
            default => 'e.id',
        };
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
     * @psalm-param array{
     *      calculation_id: int,
     *      calculation_date: \DateTimeInterface,
     *      calculation_customer: string,
     *      calculation_description: string,
     *      calculation_state: string,
     *      calculation_color: string,
     *      calculation_editable: bool
     * } $item
     *
     * @psalm-suppress MixedArrayAssignment
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
