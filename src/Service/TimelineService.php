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
 * Service for calculations timeline.
 */
class TimelineService
{
    use GroupByTrait;
    private const DATE_FORMAT = 'Y-m-d';

    public function __construct(private readonly CalculationRepository $repository)
    {
    }

    /**
     * Gets the calculations for the given date and period.
     *
     * @throws \Exception
     */
    public function current(?string $date = null, ?string $interval = null): array
    {
        $interval ??= 'P1W';
        [$today, $min_date, $max_date] = $this->getDates();
        $to = null !== $date ? new \DateTimeImmutable($date) : $max_date;
        $from = DateUtils::sub($to, $interval);

        return $this->getParameters($today, $from, $to, $interval, $min_date, $max_date);
    }

    /**
     * Gets the first calculations for the given period.
     *
     * @throws \Exception
     */
    public function first(?string $interval = null): array
    {
        $interval ??= 'P1W';
        [$today, $min_date, $max_date] = $this->getDates();
        $from = $min_date;
        $to = DateUtils::add($from, $interval);

        return $this->getParameters($today, $from, $to, $interval, $min_date, $max_date);
    }

    /**
     * Gets the last calculations for the given period.
     *
     * @throws \Exception
     */
    public function last(?string $interval = null): array
    {
        $interval ??= 'P1W';
        [$today, $min_date, $max_date] = $this->getDates();
        $to = $max_date;
        $from = DateUtils::sub($to, $interval);

        return $this->getParameters($today, $from, $to, $interval, $min_date, $max_date);
    }

    /**
     * @return Calculation[]
     */
    private function getByInterval(\DateTimeInterface $from, \DateTimeInterface $to): array
    {
        return $this->repository->getByInterval($from, $to);
    }

    /**
     * @return array{0: \DateTimeInterface|null, 1: \DateTimeInterface, 2: \DateTimeInterface}
     *
     * @throws \Exception
     */
    private function getDates(): array
    {
        $today = new \DateTimeImmutable('today');
        [$min_date, $max_date] = $this->getMinMaxDates($today);
        if ($today < $min_date || $today > $max_date) {
            $today = null;
        }

        return [$today, $min_date, $max_date];
    }

    /**
     * @return array{0: \DateTimeInterface, 1: \DateTimeInterface}
     *
     * @throws \Exception
     */
    private function getMinMaxDates(\DateTimeInterface $default): array
    {
        [$min_date, $max_date] = $this->repository->getMinMaxDates();

        return [$min_date ?? $default, $max_date ?? $default];
    }

    /**
     * @throws \Exception
     */
    private function getNextDate(\DateTimeInterface $date, string $interval, \DateTimeInterface $max_date): ?\DateTimeInterface
    {
        $nextDate = DateUtils::add($date, $interval);

        return $nextDate > $max_date ? null : $nextDate;
    }

    /**
     * @throws \Exception
     */
    private function getParameters(
        ?\DateTimeInterface $today,
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        string $interval,
        \DateTimeInterface $min_date,
        \DateTimeInterface $max_date
    ): array {
        $previous = $this->getPreviousDate($to, $interval, $min_date);
        $next = $this->getNextDate($to, $interval, $max_date);
        $calculations = $this->getByInterval($from, $to);

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

            'count' => \count($calculations),
            'data' => $this->groupByDate($calculations),
        ];
    }

    /**
     * @throws \Exception
     */
    private function getPreviousDate(\DateTimeInterface $date, string $interval, \DateTimeInterface $min_date): ?\DateTimeInterface
    {
        $previous = DateUtils::sub($date, $interval);

        return $previous <= $min_date ? null : $previous;
    }

    /**
     * @param Calculation[] $calculations
     */
    private function groupByDate(array $calculations): array
    {
        if ([] === $calculations) {
            return [];
        }
        $callback = static fn (Calculation $c): string => FormatUtils::formatDate($c->getDate(), \IntlDateFormatter::LONG);

        return $this->groupBy($calculations, $callback);
    }
}
