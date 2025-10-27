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

final class SearchControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/search', self::ROLE_USER];
        yield ['/search', self::ROLE_ADMIN];
        yield ['/search', self::ROLE_SUPER_ADMIN];
        yield ['/search?search=22', self::ROLE_USER];
        yield ['/search?search=22', self::ROLE_ADMIN];
        yield ['/search?search=22', self::ROLE_SUPER_ADMIN];
    }
}
