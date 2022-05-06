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

use App\Controller\TestController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for {@link TestController} class.
 */
class TestControllerTest extends AbstractControllerTest
{
    private const  ROUTES = [
        'clock',
        'editor',
        'flex',
        'html',
        'ipstack',
        'notifications',
        'password',
        'recaptcha',
        'swiss',
        'timeline',
        'toc',
        'translate',
        'tree',
        'union',
        'spam',
        'verify',
        ];

    public function getRoutes(): \Generator
    {
        foreach (self::ROUTES as $route) {
            yield ["/test/$route", self::ROLE_USER, Response::HTTP_FORBIDDEN];
            yield ["/test/$route", self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
            yield ["/test/$route", self::ROLE_SUPER_ADMIN];
        }
    }
}
