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

/**
 * Unit test for {@link App\Controller\HelpController} class.
 */
class HelpControllerTest extends AbstractControllerTest
{
    public function getRoutes(): array
    {
        return [
            ['/help', self::ROLE_USER],
            ['/help', self::ROLE_ADMIN],
            ['/help', self::ROLE_SUPER_ADMIN],

            ['/help/pdf', self::ROLE_USER],
            ['/help/pdf', self::ROLE_ADMIN],
            ['/help/pdf', self::ROLE_SUPER_ADMIN],

            ['/help/dialog/product.list.title', self::ROLE_USER],
            ['/help/dialog/product.list.title', self::ROLE_ADMIN],
            ['/help/dialog/product.list.title', self::ROLE_SUPER_ADMIN],

            ['/help/entity/product', self::ROLE_USER],
            ['/help/entity/product', self::ROLE_ADMIN],
            ['/help/entity/product', self::ROLE_SUPER_ADMIN],
        ];
    }
}
