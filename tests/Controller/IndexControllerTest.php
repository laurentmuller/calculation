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

use App\Controller\IndexController;

#[\PHPUnit\Framework\Attributes\CoversClass(IndexController::class)]
class IndexControllerTest extends AbstractControllerTestCase
{
    public static function getRoutes(): array
    {
        return [
            ['/', self::ROLE_DISABLED],
            ['/', self::ROLE_USER],
            ['/', self::ROLE_ADMIN],
            ['/', self::ROLE_SUPER_ADMIN],
        ];
    }
}
