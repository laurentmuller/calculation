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

class AboutControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Iterator
    {
        yield ['/about', '', Response::HTTP_FOUND];
        // redirect to login page
        yield ['/about', self::ROLE_USER];
        yield ['/about', self::ROLE_ADMIN];
        yield ['/about', self::ROLE_SUPER_ADMIN];
        yield ['/about/pdf', self::ROLE_USER];
        yield ['/about/pdf', self::ROLE_ADMIN];
        yield ['/about/pdf', self::ROLE_SUPER_ADMIN];
        yield ['/about/word', self::ROLE_USER];
        yield ['/about/word', self::ROLE_ADMIN];
        yield ['/about/word', self::ROLE_SUPER_ADMIN];
    }
}
