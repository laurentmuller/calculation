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

use App\Controller\ThemeController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[\PHPUnit\Framework\Attributes\CoversClass(ThemeController::class)]
class ThemeControllerTest extends AbstractControllerTestCase
{
    private const ROUTES = ['/theme/dialog', '/theme/save'];

    private const USERS = [self::ROLE_USER, self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN];

    public static function getRoutes(): \Generator
    {
        foreach (self::ROUTES as $route) {
            foreach (self::USERS as $user) {
                yield [$route, $user, Response::HTTP_OK, Request::METHOD_GET, true];
            }
        }
    }
}
