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

namespace App\Service;

use App\Entity\Calculation;
use App\Repository\CalculationRepository;
use App\Traits\GroupByTrait;
use App\Utils\DateUtils;
use App\Utils\FormatUtils;

/**
 * Service for the calculation timeline.
 *
 * @phpstan-type ParametersType=array{
 *     from: \DateTimeImmutable,
 *     to: \DateTimeImmutable,
 *     interval: string,
 *     date: string,
 *     min_date: \DateTimeImmutable,
 *     max_date: \DateTimeImmutable,
 *     today: ?string,
 *     previous: ?string,
 *     next: ?string,
 *     count: int,
 *     data: array<string, Calculation[]>}
 */
readonly class TimelineService
{
    use GroupByTrait;

    /**
     * The date format for HTML input.
     */
    public const DATE_FORMAT = 'Y-m-d';

    private const DEFAULT_INTERVAL = 'P1W';

    public function __construct(private CalculationRepository $repository)
    {
    }

    /**
     * Gets the calculations for the given date and period.
     *
     * @throws \Exception
     *
     * @phpstan-return ParametersType
     */
    public function current(?string $date = null, ?string $interval = null): array
    {
        $interval ??= self::DEFAULT_INTERVAL;
        [$today, $min_date, $max_date] = $this->getDates();
        $to = null !== $date ? DateUtils::createDateTimeImmutable($date) : $max_date;
        $from = DateUtils::sub($to, $interval);

        return $this->getParameters($today, $from, $to, $interval, $min_date, $max_date);
    }

    /**
     * Gets the first calculations for the given period.
     *
     * @throws \Exception
     *
     * @phpstan-return ParametersType
     */
    public function first(?string $interval = null): array
    {
        $interval ??= self::DEFAULT_INTERVAL;
        [$today, $min_date, $max_date] = $this->getDates();
        $from = $min_date;
        $to = DateUtils::add($from, $interval);

        return $this->getParameters($today, $from, $to, $interval, $min_date, $max_date);
    }

    /**
     * Gets the last calculations for the given period.
     *
     * @throws \Exception
     *
     * @phpstan-return ParametersType
     */
    public function last(?string $interval = null): array
    {
        $interval ??= self::DEFAULT_INTERVAL;
        [$today, $min_date, $max_date] = $this->getDates();
        $to = $max_date;
        $from = DateUtils::sub($to, $interval);

        return $this->getParameters($today, $from, $to, $interval, $min_date, $max_date);
    }

    /**
     * @return array{0: int, 1: array<string, Calculation[]>}
     */
    private function getCalculations(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $calculations = $this->repository->getByInterval($from, $to);
        if ([] === $calculations) {
            return [0, []];
        }

        /** @var array<string, Calculation[]> $grouped */
        $grouped = $this->groupBy(
            $calculations,
            static fn (Calculation $c): string => FormatUtils::formatDate($c->getDate(), \IntlDateFormatter::LONG)
        );

        return [\count($calculations), $grouped];
    }

    /**
     * @return array{0: \DateTimeImmutable|null, 1: \DateTimeImmutable, 2: \DateTimeImmutable}
     *
     * @throws \Exception
     */
    private function getDates(): array
    {
        $today = DateUtils::createDateTimeImmutable('today');
        [$min_date, $max_date] = $this->getMinMaxDates($today);
        if ($today < $min_date || $today > $max_date) {
            $today = null;
        }

        return [$today, $min_date, $max_date];
    }

    /**
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}
     *
     * @throws \Exception
     */
    private function getMinMaxDates(\DateTimeImmutable $default): array
    {
        [$min_date, $max_date] = $this->repository->getMinMaxDates();

        return [$min_date ?? $default, $max_date ?? $default];
    }

    /**
     * @throws \Exception
     */
    private function getNextDate(
        \DateTimeImmutable $date,
        string $interval,
        \DateTimeImmutable $max_date
    ): ?\DateTimeImmutable {
        $nextDate = DateUtils::add($date, $interval);

        return $nextDate > $max_date ? null : $nextDate;
    }

    /**
     * @throws \Exception
     *
     * @phpstan-return ParametersType
     */
    private function getParameters(
        ?\DateTimeImmutable $today,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        string $interval,
        \DateTimeImmutable $min_date,
        \DateTimeImmutable $max_date
    ): array {
        $previous = $this->getPreviousDate($to, $interval, $min_date);
        $next = $this->getNextDate($to, $interval, $max_date);
        [$count, $calculations] = $this->getCalculations($from, $to);

        return [
            'from' => $from,
            'to' => $to,
            'interval' => $interval,
            'date' => $to->format(self::DATE_FORMAT),

            'min_date' => $min_date,
            'max_date' => $max_date,

            'today' => $today?->format(self::DATE_FORMAT),
            'previous' => $previous?->format(self::DATE_FORMAT),
            'next' => $next?->format(self::DATE_FORMAT),

            'count' => $count,
            'data' => $calculations,
        ];
    }

    /**
     * @throws \Exception
     */
    private function getPreviousDate(
        \DateTimeImmutable $date,
        string $interval,
        \DateTimeImmutable $min_date
    ): ?\DateTimeImmutable {
        $previous = DateUtils::sub($date, $interval);

        return $previous <= $min_date ? null : $previous;
    }
}
