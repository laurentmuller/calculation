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

final class ThemeControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Generator
    {
        $routes = [
            '/theme/dialog',
            '/theme/save',
        ];
        foreach ($routes as $route) {
            foreach (self::DEFAULT_USERS as $user) {
                yield [$route, $user, 'xmlHttpRequest' => true];
            }
        }
    }
}
