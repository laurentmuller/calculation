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

use App\Controller\TimelineController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for {@link TimelineController} class.
 */
class TimelineControllerTest extends AbstractControllerTest
{
    private const ROUTES = [
        '/test/timeline',
        '/test/timeline/first',
        '/test/timeline/last',
    ];

    public static function getRoutes(): \Generator
    {
        foreach (self::ROUTES as $route) {
            yield [$route, self::ROLE_USER, Response::HTTP_FORBIDDEN];
            yield [$route, self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
            yield [$route, self::ROLE_SUPER_ADMIN];
        }
    }
}