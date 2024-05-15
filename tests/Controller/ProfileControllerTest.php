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

use App\Controller\ProfileController;

#[\PHPUnit\Framework\Attributes\CoversClass(ProfileController::class)]
class ProfileControllerTest extends AbstractControllerTestCase
{
    public static function getRoutes(): \Iterator
    {
        yield ['/user/profile/password', self::ROLE_USER];
        yield ['/user/profile/password', self::ROLE_ADMIN];
        yield ['/user/profile/password', self::ROLE_SUPER_ADMIN];

        yield ['/user/profile/edit', self::ROLE_USER];
        yield ['/user/profile/edit', self::ROLE_ADMIN];
        yield ['/user/profile/edit', self::ROLE_SUPER_ADMIN];
    }
}
