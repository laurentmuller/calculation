<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Controller;

use App\Calendar\CalculationsDay;
use App\Calendar\CalculationsMonth;
use App\Calendar\CalculationsWeek;
use App\Calendar\Calendar;
use App\Calendar\CalendarService;
use App\Calendar\Month;
use App\Calendar\Week;
use App\Entity\Calculation;
use App\Repository\CalculationRepository;
use App\Util\DateUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller to display calendar.
 *
 * @author Laurent Muller
 *
 * @Route("/calendar")
 * @IsGranted("ROLE_USER")
 */
class CalendarController extends AbstractController
{
    /**
     * Display a month of a calendar.
     *
     * @Route("/month/{year}/{month}", name="calendar_month", requirements={"year": "\d+",
     * "month": "\d+"})
     *
     * @param CalendarService       $service    the service to generate the calendar
     * @param CalculationRepository $repository the repository to query calculations
     * @param int|null              $year       the year to search for or <code>null</code> for the current
     *                                          year
     * @param int|null              $month      the month to search for or <code>null</code> for the current
     *                                          month
     */
    public function month(CalendarService $service, CalculationRepository $repository,
        ?int $year = null, ?int $month = null): Response
    {
        // validate values
        $year = $this->validateYear($year);
        $month = $this->validateMonth($month);

        // generate
        $calendar = $this->generate($service, $year);
        $calculations = $repository->getForMonth($year, $month);
        $this->merge($calendar, $calculations);

        // months
        $yearsMonths = $repository->getCalendarYearsMonths();
        $today = $this->todayMonth($yearsMonths, $year, $month);
        $previous = $this->previousMonth($yearsMonths, $year, $month);
        $next = $this->nextMonth($yearsMonths, $year, $month);
        $currentMonth = $calendar->getMonth(Month::formatKey($year, $month));

        return $this->render('calendar/calendar_month.html.twig',
            [
                'calendar' => $calendar,
                'month' => $currentMonth,
                'calculations' => $calculations,
                'today' => $today,
                'previous' => $previous,
                'next' => $next,
            ]);
    }

    /**
     * Display a week of a calendar.
     *
     * @Route("/week/{year}/{week}", name="calendar_week", requirements={"year": "\d+",
     * "week": "\d+"})
     *
     * @param CalendarService       $service    the service to generate the calendar
     * @param CalculationRepository $repository the repository to query calculations
     * @param int|null              $year       the year to search for or <code>null</code> for the current
     *                                          year
     * @param int|null              $week       the week to search for or <code>null</code> for the current
     *                                          week
     */
    public function week(CalendarService $service, CalculationRepository $repository,
        ?int $year = null, ?int $week = null): Response
    {
        // validate values
        $year = $this->validateYear($year);
        $week = $this->validateWeek($week);

        // generate
        $calendar = $this->generate($service, $year);
        $calculations = $repository->getForWeek($year, $week);
        $this->merge($calendar, $calculations);

        $yearsWeeks = $repository->getCalendarYearsWeeks();
        $today = $this->todayWeek($yearsWeeks, $year, $week);
        $previous = $this->previousWeek($yearsWeeks, $year, $week);
        $next = $this->nextWeek($yearsWeeks, $year, $week);
        $currentWeek = $calendar->getWeek(Week::formatKey($year, $week));

        return $this->render('calendar/calendar_week.html.twig',
            [
                'calendar' => $calendar,
                'week' => $currentWeek,
                'calculations' => $calculations,
                'today' => $today,
                'previous' => $previous,
                'next' => $next,
            ]);
    }

    /**
     * Display a calendar.
     *
     * @Route("/year/{year}", name="calendar_year", requirements={"year": "\d+" })
     *
     * @param CalendarService       $service    the service to generate the calendar
     * @param CalculationRepository $repository the repository to query calculations
     * @param int|null              $year       the year to search for or <code>null</code> for the current
     *                                          year
     */
    public function year(CalendarService $service, CalculationRepository $repository, ?int $year = null): Response
    {
        // validate year
        $year = $this->validateYear($year);

        // generate
        $calendar = $this->generate($service, $year);
        $calculations = $repository->getForYear($year);
        $this->merge($calendar, $calculations);

        // get previous and next years
        $years = $repository->getCalendarYears();
        $today = $this->todayYear($years, $year);
        $previous = $this->previousYear($years, $year);
        $next = $this->nextYear($years, $year);

        return $this->render('calendar/calendar_year.html.twig',
            [
                'calendar' => $calendar,
                'calculations' => $calculations,
                'years' => $years,
                'today' => $today,
                'previous' => $previous,
                'next' => $next,
            ]);
    }

    /**
     * Generate a calendar for the given year.
     *
     * @param CalendarService $service the service
     * @param int             $year    the year to generate
     */
    private function generate(CalendarService $service, int $year): Calendar
    {
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
     * @return int[]|bool the next year and month, if found; false otherwise
     */
    private function nextMonth(array $yearsMonths, int $year, int $month)
    {
        $yearMonth = $year * 1000 + $month;
        $filtered = \array_filter($yearsMonths,
            function (array $current) use ($yearMonth) {
                return $current['year_month'] > $yearMonth;
            });

        return \reset($filtered);
    }

    /**
     * Gets the next year and week.
     *
     * @param array $yearsWeeks the array of all years and weeks
     * @param int   $year       the current year
     * @param int   $week       the current week
     *
     * @return int[]|bool the next year and month, if found; false otherwise
     */
    private function nextWeek(array $yearsWeeks, int $year, int $week)
    {
        $yearWeek = $year * 1000 + $week;
        $filtered = \array_filter($yearsWeeks,
            function (array $current) use ($yearWeek) {
                return $current['year_week'] > $yearWeek;
            });

        return \reset($filtered);
    }

    /**
     * Gets the next year.
     *
     * @param int[] $years the availaible years
     * @param int   $year  the current year
     *
     * @return int|bool the next year, if found; false otherwise
     */
    private function nextYear(array $years, int $year)
    {
        $filtered = \array_filter($years,
            function (int $current) use ($year) {
                return $current > $year;
            });

        return \reset($filtered);
    }

    /**
     * Gets the previous year and month.
     *
     * @param array $yearsMonths the array of all years and months
     * @param int   $year        the current year
     * @param int   $month       the current month
     *
     * @return int[]|bool the previous year and month, if found; false otherwise
     */
    private function previousMonth(array $yearsMonths, int $year, int $month)
    {
        $yearMonth = $year * 1000 + $month;
        $filtered = \array_filter($yearsMonths,
            function (array $current) use ($yearMonth) {
                return $current['year_month'] < $yearMonth;
            });

        return \end($filtered);
    }

    /**
     * Gets the previous year and week.
     *
     * @param array $yearsWeeks the array of all years and weeks
     * @param int   $year       the current year
     * @param int   $week       the current week
     *
     * @return int[]|bool the previous year and month, if found; false otherwise
     */
    private function previousWeek(array $yearsWeeks, int $year, int $week)
    {
        $yearWeek = $year * 1000 + $week;
        $filtered = \array_filter($yearsWeeks,
            function (array $current) use ($yearWeek) {
                return $current['year_week'] < $yearWeek;
            });

        return \end($filtered);
    }

    /**
     * Gets the previous year.
     *
     * @param int[] $years the availaible years
     * @param int   $year  the current year
     *
     * @return int|bool the previous year, if found; false otherwise
     */
    private function previousYear(array $years, int $year)
    {
        $filtered = \array_filter($years,
            function (int $current) use ($year) {
                return $current < $year;
            });

        return \end($filtered);
    }

    /**
     * Gets the today year and month.
     *
     * @param array $yearsMonths the array of all years and months
     * @param int   $year        the current year
     * @param int   $month       the current month
     *
     * @return int[]|bool the today year and month, if found; null otherwise
     */
    private function todayMonth(array $yearsMonths, int $year, int $month)
    {
        $todayYear = (int) \date('Y');
        $todayMonth = (int) \date('n');
        if ($year !== $todayYear || $month !== $todayMonth) {
            $yearMonth = $todayYear * 1000 + $todayMonth;
            $filtered = \array_filter($yearsMonths,
                function (array $current) use ($yearMonth) {
                    return $current['year_month'] === $yearMonth;
                });

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
     * @return int[]|bool the today year and weak, if found; null otherwise
     */
    private function todayWeek(array $yearsWeeks, int $year, int $week)
    {
        $todayYear = (int) \date('Y');
        $todayWeek = (int) \date('W');
        if ($year !== $todayYear || $week !== $todayWeek) {
            $yearWeek = $year * 1000 + $week;
            $filtered = \array_filter($yearsWeeks,
                function (array $current) use ($yearWeek) {
                    return $current['year_week'] === $yearWeek;
                });

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
     * @return int|bool the today year, if found; null otherwise
     */
    private function todayYear(array $years, int $year)
    {
        $todayYear = (int) \date('Y');
        if ($year !== $todayYear && \in_array($todayYear, $years, true)) {
            return $todayYear;
        }

        return false;
    }

    /**
     * Validate the given month.
     *
     * @param int $month the optional month to validate
     *
     * @return int a valid month
     *
     * @throws NotFoundHttpException if the month is not witin the range from 1 to 12
     *                               inclusive
     */
    private function validateMonth(?int $month = null): int
    {
        $month = (int) ($month ?? \date('n'));
        if ($month < 1 || $month > 12) {
            throw $this->createNotFoundException($this->trans('calendar.invalid_month'));
        }

        return $month;
    }

    /**
     * Validate the given week.
     *
     * @param int $week the optional week to validate
     *
     * @return int a valid week
     *
     * @throws NotFoundHttpException if the week is not witin the range from 1 to 53
     *                               inclusive
     */
    private function validateWeek(?int $week = null): int
    {
        $week = (int) ($week ?? \date('W'));
        if ($week < 1 || $week > 53) {
            throw $this->createNotFoundException($this->trans('calendar.invalid_week'));
        }

        return $week;
    }

    /**
     * Validate the given year.
     *
     * @param int $year the optional year to validate
     *
     * @return int a valid year
     */
    private function validateYear(?int $year = null): int
    {
        return DateUtils::completYear((int) ($year ?? \date('Y')));
    }
}