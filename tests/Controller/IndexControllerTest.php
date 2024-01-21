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
    public static function getRoutes(): \Iterator
    {
        yield ['/', self::ROLE_DISABLED];
        yield ['/', self::ROLE_USER];
        yield ['/', self::ROLE_ADMIN];
        yield ['/', self::ROLE_SUPER_ADMIN];
    }
}
