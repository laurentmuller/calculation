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

namespace App\Tests\Controller;

/**
 * Unit test for {@link App\Controller\CalendarController} class.
 *
 * @author Laurent Muller
 */
class CalendarControllerTest extends AbstractControllerTest
{
    public function getRoutes(): array
    {
        return [
            ['/calendar/month', self::ROLE_USER],
            ['/calendar/month', self::ROLE_ADMIN],
            ['/calendar/month', self::ROLE_SUPER_ADMIN],

            ['/calendar/week', self::ROLE_USER],
            ['/calendar/week', self::ROLE_ADMIN],
            ['/calendar/week', self::ROLE_SUPER_ADMIN],

            ['/calendar/year', self::ROLE_USER],
            ['/calendar/year', self::ROLE_ADMIN],
            ['/calendar/year', self::ROLE_SUPER_ADMIN],
        ];
    }
}
