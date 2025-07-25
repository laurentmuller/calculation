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

namespace App\Controller;

use App\Attribute\GetRoute;
use App\Calendar\CalculationsDay;
use App\Calendar\CalculationsMonth;
use App\Calendar\CalculationsWeek;
use App\Calendar\Calendar;
use App\Calendar\CalendarService;
use App\Calendar\Month;
use App\Calendar\Week;
use App\Entity\Calculation;
use App\Interfaces\RoleInterface;
use App\Repository\CalculationRepository;
use App\Utils\DateUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to display calendar.
 */
#[Route(path: '/calendar', name: 'calendar_')]
#[IsGranted(RoleInterface::ROLE_USER)]
class CalendarController extends AbstractController
{
    public function __construct(private readonly CalculationRepository $repository)
    {
    }

    /**
     * Display a month.
     *
     * @param ?int $year  the year to search for or <code>null</code> for the current year
     * @param ?int $month the month to search for or <code>null</code> for the current month
     *
     * @throws \App\Calendar\CalendarException
     */
    #[GetRoute(path: '/month/{year}/{month}', name: 'month', requirements: ['year' => Requirement::POSITIVE_INT, 'month' => Requirement::POSITIVE_INT])]
    public function month(?int $year = null, ?int $month = null): Response
    {
        $year = $this->validateYear($year);
        $month = $this->validateMonth($month);
        $calendar = $this->generate($year);

        $calculations = $this->repository->getForMonth($year, $month);
        $this->merge($calendar, $calculations);

        $yearsMonths = $this->repository->getCalendarYearsMonths();
        $today = $this->todayMonth($yearsMonths, $year, $month);
        $previous = $this->previousMonth($yearsMonths, $year, $month);
        $next = $this->nextMonth($yearsMonths, $year, $month);
        $currentMonth = $calendar->getMonth(Month::formatKey($year, $month));

        $parameters = [
            'calendar' => $calendar,
            'month' => $currentMonth,
            'calculations' => $calculations,
            'today' => $today,
            'previous' => $previous,
            'next' => $next,
        ];

        return $this->render('calendar/calendar_month.html.twig', $parameters);
    }

    /**
     * Display a week of a calendar.
     *
     * @param ?int $year the year to search for or <code>null</code> for the current year
     * @param ?int $week the week to search for or <code>null</code> for the current week
     *
     * @throws \App\Calendar\CalendarException
     */
    #[GetRoute(path: '/week/{year}/{week}', name: 'week', requirements: ['year' => Requirement::POSITIVE_INT, 'week' => Requirement::POSITIVE_INT])]
    public function week(?int $year = null, ?int $week = null): Response
    {
        $year = $this->validateYear($year);
        $week = $this->validateWeek($week);
        $calendar = $this->generate($year);

        $calculations = $this->repository->getForWeek($year, $week);
        $this->merge($calendar, $calculations);

        $yearsWeeks = $this->repository->getCalendarYearsWeeks();
        $today = $this->todayWeek($yearsWeeks, $year, $week);
        $previous = $this->previousWeek($yearsWeeks, $year, $week);
        $next = $this->nextWeek($yearsWeeks, $year, $week);
        $currentWeek = $calendar->getWeek(Week::formatKey($year, $week));
        $startDate = DateUtils::createDatePoint()->setISODate($year, $week);
        $endDate = DateUtils::add($startDate, 'P6D');

        $parameters = [
            'calendar' => $calendar,
            'week' => $currentWeek,
            'calculations' => $calculations,
            'today' => $today,
            'previous' => $previous,
            'next' => $next,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        return $this->render('calendar/calendar_week.html.twig', $parameters);
    }

    /**
     * Display a calendar.
     *
     * @param ?int $year the year to search for or <code>null</code> for the current year
     *
     * @throws \App\Calendar\CalendarException
     */
    #[GetRoute(path: '/year/{year}', name: 'year', requirements: ['year' => Requirement::POSITIVE_INT])]
    public function year(?int $year = null): Response
    {
        $year = $this->validateYear($year);
        $calendar = $this->generate($year);

        $calculations = $this->repository->getForYear($year);
        $this->merge($calendar, $calculations);

        $years = $this->repository->getCalendarYears();
        $today = $this->todayYear($years, $year);
        $previous = $this->previousYear($years, $year);
        $next = $this->nextYear($years, $year);

        $parameters = [
            'calendar' => $calendar,
            'calculations' => $calculations,
            'years' => $years,
            'today' => $today,
            'previous' => $previous,
            'next' => $next,
        ];

        return $this->render('calendar/calendar_year.html.twig', $parameters);
    }

    /**
     * Generate a calendar for the given year.
     *
     * @param int $year the year to generate
     *
     * @throws \App\Calendar\CalendarException
     */
    private function generate(int $year): Calendar
    {
        $service = new CalendarService();
        $service->setMonthModel(CalculationsMonth::class)
            ->setWeekModel(CalculationsWeek::class)
            ->setDayModel(CalculationsDay::class);

        return $service->generate($year);
    }

    /**
     * Merges calculation to the calendar.
     *
     * @param Calendar      $calendar     the calendar to update
     * @param Calculation[] $calculations the calculations to merge
     */
    private function merge(Calendar $calendar, array $calculations): void
    {
        foreach ($calculations as $calculation) {
            $date = $calculation->getDate();

            $day = $calendar->getDay($date);
            if ($day instanceof CalculationsDay) {
                $day->addCalculation($calculation);
            }

            $week = $calendar->getWeek($date);
            if ($week instanceof CalculationsWeek) {
                $week->addCalculation($calculation);
            }

            $month = $calendar->getMonth($date);
            if ($month instanceof CalculationsMonth) {
                $month->addCalculation($calculation);
            }
        }
    }

    /**
     * Gets the next year and month.
     *
     * @param array $yearsMonths the array of all years and months
     * @param int   $year        the current year
     * @param int   $month       the current month
     *
     * @return int[]|false the next year and month, if found; false otherwise
     */
    private function nextMonth(array $yearsMonths, int $year, int $month): array|false
    {
        $yearMonth = $year * 1000 + $month;
        /** @phpstan-var array<int[]> $filtered */
        $filtered = \array_filter($yearsMonths, fn (array $current): bool => $current['year_month'] > $yearMonth);

        return \reset($filtered);
    }

    /**
     * Gets the next year and week.
     *
     * @param array $yearsWeeks the array of all years and weeks
     * @param int   $year       the current year
     * @param int   $week       the current week
     *
     * @return int[]|false the next year and week, if found; false otherwise
     */
    private function nextWeek(array $yearsWeeks, int $year, int $week): array|false
    {
        $yearWeek = $year * 1000 + $week;
        /** @phpstan-var array<int[]> $filtered */
        $filtered = \array_filter($yearsWeeks, fn (array $current): bool => $current['year_week'] > $yearWeek);

        return \reset($filtered);
    }

    /**
     * Gets the next year.
     *
     * @param int[] $years the available years
     * @param int   $year  the current year
     *
     * @return int|false the next year, if found; false otherwise
     */
    private function nextYear(array $years, int $year): int|false
    {
        $filtered = \array_filter($years, fn (int $current): bool => $current > $year);

        return \reset($filtered);
    }

    /**
     * Gets the previous year and month.
     *
     * @param array $yearsMonths the array of all years and months
     * @param int   $year        the current year
     * @param int   $month       the current month
     *
     * @return int[]|false the previous year and month, if found; false otherwise
     */
    private function previousMonth(array $yearsMonths, int $year, int $month): array|false
    {
        $yearMonth = $year * 1000 + $month;
        /** @phpstan-var array<int[]> $filtered */
        $filtered = \array_filter($yearsMonths, fn (array $current): bool => $current['year_month'] < $yearMonth);

        return \reset($filtered);
    }

    /**
     * Gets the previous year and week.
     *
     * @param array $yearsWeeks the array of all years and weeks
     * @param int   $year       the current year
     * @param int   $week       the current week
     *
     * @return int[]|false the previous year and week, if found; false otherwise
     */
    private function previousWeek(array $yearsWeeks, int $year, int $week): array|false
    {
        $yearWeek = $year * 1000 + $week;
        /** @phpstan-var array<int[]> $filtered */
        $filtered = \array_filter($yearsWeeks, fn (array $current): bool => $current['year_week'] < $yearWeek);

        return \reset($filtered);
    }

    /**
     * Gets the previous year.
     *
     * @param int[] $years the available years
     * @param int   $year  the current year
     *
     * @return int|false the previous year, if found; false otherwise
     */
    private function previousYear(array $years, int $year): int|false
    {
        $filtered = \array_filter($years, fn (int $current): bool => $current < $year);

        return \end($filtered);
    }

    /**
     * Gets the today year and month.
     *
     * @param array $yearsMonths the array of all years and months
     * @param int   $year        the current year
     * @param int   $month       the current month
     *
     * @return int[]|false the today year and month, if found; null otherwise
     */
    private function todayMonth(array $yearsMonths, int $year, int $month): array|false
    {
        $todayYear = DateUtils::getYear();
        $todayMonth = DateUtils::getMonth();
        if ($year !== $todayYear || $month !== $todayMonth) {
            $yearMonth = $todayYear * 1000 + $todayMonth;
            /** @phpstan-var array<int[]> $filtered */
            $filtered = \array_filter($yearsMonths, fn (array $current): bool => $current['year_month'] === $yearMonth);

            return \reset($filtered);
        }

        return false;
    }

    /**
     * Gets the today year and weak.
     *
     * @param array $yearsWeeks the array of all years and weeks
     * @param int   $year       the current year
     * @param int   $week       the current week
     *
     * @return int[]|false the today year and weak, if found; null otherwise
     */
    private function todayWeek(array $yearsWeeks, int $year, int $week): array|false
    {
        $todayYear = DateUtils::getYear();
        $todayWeek = DateUtils::getWeek();
        if ($year !== $todayYear || $week !== $todayWeek) {
            $yearWeek = $year * 1000 + $week;
            /** @phpstan-var array<int[]> $filtered */
            $filtered = \array_filter($yearsWeeks, fn (array $current): bool => $current['year_week'] === $yearWeek);

            return \reset($filtered);
        }

        return false;
    }

    /**
     * Gets the today year.
     *
     * @param int[] $years the array of all years
     * @param int   $year  the current year
     *
     * @return int|false the today year, if found; null otherwise
     */
    private function todayYear(array $years, int $year): int|false
    {
        $todayYear = DateUtils::getYear();
        if ($year !== $todayYear && \in_array($todayYear, $years, true)) {
            return $todayYear;
        }

        return false;
    }

    /**
     * Validate the given month.
     *
     * @param ?int $month the optional month to validate
     *
     * @return int a valid month
     *
     * @throws NotFoundHttpException if the month is not within the range from 1 to 12 inclusive
     */
    private function validateMonth(?int $month = null): int
    {
        $month ??= DateUtils::getMonth();
        if ($month < 1 || $month > 12) {
            throw $this->createTranslatedNotFoundException('calendar.invalid_month');
        }

        return $month;
    }

    /**
     * Validate the given week.
     *
     * @param ?int $week the optional week to validate
     *
     * @return int a valid week
     *
     * @throws NotFoundHttpException if the week is not within the range from 1 to 53 inclusive
     */
    private function validateWeek(?int $week = null): int
    {
        $week ??= DateUtils::getWeek();
        if ($week < 1 || $week > 53) {
            throw $this->createTranslatedNotFoundException('calendar.invalid_week');
        }

        return $week;
    }

    /**
     * Validate the given year.
     *
     * @param ?int $year the optional year to validate
     *
     * @return int a valid year with 4 digits
     */
    private function validateYear(?int $year = null): int
    {
        return DateUtils::completYear($year);
    }
}
