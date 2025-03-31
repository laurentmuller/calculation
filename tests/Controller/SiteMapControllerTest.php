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

class SiteMapControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/sitemap', self::ROLE_USER];
        yield ['/sitemap', self::ROLE_ADMIN];
        yield ['/sitemap', self::ROLE_SUPER_ADMIN];
    }
}
