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

use Symfony\Component\HttpFoundation\Response;

class TestControllerTest extends ControllerTestCase
{
    private const ROUTES = [
        'editor',
        'label',
        'pdf',
        'word',
        'colors',
        'fontawesome',
        'memory',
        'notifications',
        'password',
        'recaptcha',
        'search',
        'swiss',
        'translate',
        'tree',
    ];

    public static function getRoutes(): \Generator
    {
        foreach (self::ROUTES as $route) {
            yield ["/test/$route", self::ROLE_USER, Response::HTTP_FORBIDDEN];
            yield ["/test/$route", self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
            yield ["/test/$route", self::ROLE_SUPER_ADMIN];
        }
    }
}
