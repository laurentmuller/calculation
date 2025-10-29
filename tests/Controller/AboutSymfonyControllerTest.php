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

final class AboutSymfonyControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/about/symfony/content', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/about/symfony/content', self::ROLE_ADMIN];
        yield ['/about/symfony/content', self::ROLE_SUPER_ADMIN];
        yield ['/about/symfony/excel', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/about/symfony/excel', self::ROLE_ADMIN];
        yield ['/about/symfony/excel', self::ROLE_SUPER_ADMIN];
        yield ['/about/symfony/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/about/symfony/pdf', self::ROLE_ADMIN];
        yield ['/about/symfony/pdf', self::ROLE_SUPER_ADMIN];

        $query = '/about/symfony/license?name=';
        yield [$query . 'symfony/cache-contracts', self::ROLE_ADMIN, Response::HTTP_OK, Request::METHOD_GET, true];
        yield [$query . 'fake', self::ROLE_ADMIN, Response::HTTP_OK, Request::METHOD_GET, true];

        $query = '/about/symfony/package?name=';
        yield [$query . 'symfony/cache-contracts', self::ROLE_ADMIN, Response::HTTP_OK, Request::METHOD_GET, true];
        yield [$query . 'fake', self::ROLE_ADMIN, Response::HTTP_OK, Request::METHOD_GET, true];
    }
}
