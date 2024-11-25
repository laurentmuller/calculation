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

class AboutMySqlControllerTest extends ControllerTestCase
{
    public static function getRoutes(): \Iterator
    {
        yield ['/about/mysql/content', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/about/mysql/content', self::ROLE_ADMIN];
        yield ['/about/mysql/content', self::ROLE_SUPER_ADMIN];
        yield ['/about/mysql/excel', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/about/mysql/excel', self::ROLE_ADMIN];
        yield ['/about/mysql/excel', self::ROLE_SUPER_ADMIN];
        yield ['/about/mysql/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/about/mysql/pdf', self::ROLE_ADMIN];
        yield ['/about/mysql/pdf', self::ROLE_SUPER_ADMIN];
    }
}
