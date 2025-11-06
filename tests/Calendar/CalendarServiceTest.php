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

namespace App\Tests\Calendar;

use App\Calendar\Calendar;
use App\Calendar\CalendarException;
use App\Calendar\CalendarService;
use PHPUnit\Framework\TestCase;

final class CalendarServiceTest extends TestCase
{
    /**
     * @throws CalendarException
     */
    public function testGenerate(): void
    {
        $service = new CalendarService();
        $actual = $service->generate();
        self::assertSame(Calendar::DEFAULT_DAY_MODEL, $actual->getDayModel());
        self::assertSame(Calendar::DEFAULT_WEEK_MODEL, $actual->getWeekModel());
        self::assertSame(Calendar::DEFAULT_MONTH_MODEL, $actual->getMonthModel());
    }

    public function testGetCalendarModel(): void
    {
        $service = new CalendarService();
        self::assertSame(Calendar::class, $service->getCalendarModel());
    }

    public function testNotExistClass(): void
    {
        self::expectException(CalendarException::class);
        self::expectExceptionMessage("Class 'fake' not found.");
        $service = new CalendarService();
        $service->setCalendarModel('fake'); // @phpstan-ignore argument.type
    }

    /**
     * @throws CalendarException
     */
    public function testSetCalendarModel(): void
    {
        $service = new CalendarService();
        $service->setCalendarModel(Calendar::class);
        self::assertSame(Calendar::class, $service->getCalendarModel());
    }
}
