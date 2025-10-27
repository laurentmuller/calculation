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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class NavigationControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Generator
    {
        $routes = [
            '/navigation/horizontal',
            '/navigation/vertical',
        ];
        $users = [
            self::ROLE_USER,
            self::ROLE_ADMIN,
            self::ROLE_SUPER_ADMIN,
        ];
        foreach ($routes as $route) {
            foreach ($users as $user) {
                yield [$route, $user, Response::HTTP_OK, Request::METHOD_GET, true];
            }
        }
    }
}
