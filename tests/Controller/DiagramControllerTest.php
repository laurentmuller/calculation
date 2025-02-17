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

class DiagramControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Iterator
    {
        yield ['/diagram', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/diagram', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
        yield ['/diagram', self::ROLE_SUPER_ADMIN];

        yield ['/diagram?name=fake_file_name', self::ROLE_SUPER_ADMIN, Response::HTTP_NOT_FOUND];

        yield ['/diagram/load', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/diagram/load', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN];
        yield ['/diagram/load', self::ROLE_SUPER_ADMIN];

        yield ['/diagram/load?name=fake_file_name', self::ROLE_SUPER_ADMIN];
    }
}
