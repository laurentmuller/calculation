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

namespace App\Tests\Controller;

class CalendarControllerTest extends ControllerTestCase
{
    public static function getRoutes(): \Iterator
    {
        yield ['/calendar/month', self::ROLE_USER];
        yield ['/calendar/month', self::ROLE_ADMIN];
        yield ['/calendar/month', self::ROLE_SUPER_ADMIN];
        yield ['/calendar/week', self::ROLE_USER];
        yield ['/calendar/week', self::ROLE_ADMIN];
        yield ['/calendar/week', self::ROLE_SUPER_ADMIN];
        yield ['/calendar/year', self::ROLE_USER];
        yield ['/calendar/year', self::ROLE_ADMIN];
        yield ['/calendar/year', self::ROLE_SUPER_ADMIN];
    }
}
