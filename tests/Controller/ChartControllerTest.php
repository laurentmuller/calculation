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

use App\Controller\ChartController;

#[\PHPUnit\Framework\Attributes\CoversClass(ChartController::class)]
class ChartControllerTest extends AbstractControllerTestCase
{
    public static function getRoutes(): array
    {
        return [
            ['/chart/month', self::ROLE_USER],
            ['/chart/month', self::ROLE_ADMIN],
            ['/chart/month', self::ROLE_SUPER_ADMIN],

            ['/chart/state', self::ROLE_USER],
            ['/chart/state', self::ROLE_ADMIN],
            ['/chart/state', self::ROLE_SUPER_ADMIN],
        ];
    }
}
