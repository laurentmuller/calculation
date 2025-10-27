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

final class AboutDatabaseControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/about/database/content', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/about/database/content', self::ROLE_ADMIN];
        yield ['/about/database/content', self::ROLE_SUPER_ADMIN];
        yield ['/about/database/excel', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/about/database/excel', self::ROLE_ADMIN];
        yield ['/about/database/excel', self::ROLE_SUPER_ADMIN];
        yield ['/about/database/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/about/database/pdf', self::ROLE_ADMIN];
        yield ['/about/database/pdf', self::ROLE_SUPER_ADMIN];
    }
}
