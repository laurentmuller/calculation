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

final class AkismetControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Generator
    {
        $routes = [
            'activity',
            'spam',
            'usage',
            'verify',
        ];
        foreach ($routes as $route) {
            yield ['/akismet/' . $route, self::ROLE_USER, Response::HTTP_FORBIDDEN];
            yield ['/akismet/' . $route, self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
            yield ['/akismet/' . $route, self::ROLE_SUPER_ADMIN];
        }
    }
}
