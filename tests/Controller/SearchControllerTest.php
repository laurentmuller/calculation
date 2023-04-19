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

use App\Controller\SearchController;

#[\PHPUnit\Framework\Attributes\CoversClass(SearchController::class)]
class SearchControllerTest extends AbstractControllerTestCase
{
    public static function getRoutes(): array
    {
        return [
            ['/search', self::ROLE_USER],
            ['/search', self::ROLE_ADMIN],
            ['/search', self::ROLE_SUPER_ADMIN],

            ['/search?search=22', self::ROLE_USER],
            ['/search?search=22', self::ROLE_ADMIN],
            ['/search?search=22', self::ROLE_SUPER_ADMIN],
        ];
    }
}
