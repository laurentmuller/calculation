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

/**
 * Unit test for {@link AdminController} class.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class AdminControllerTest extends AbstractControllerTest
{
    public function getRoutes(): array
    {
        return [
            ['/admin/clear', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/admin/clear', self::ROLE_ADMIN],
            ['/admin/clear', self::ROLE_SUPER_ADMIN],

            ['/admin/parameters', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/admin/parameters', self::ROLE_ADMIN],
            ['/admin/parameters', self::ROLE_SUPER_ADMIN],

            ['/admin/rights/admin', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/admin/rights/admin', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN],
            ['/admin/rights/admin', self::ROLE_SUPER_ADMIN],

            ['/admin/rights/user', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/admin/rights/user', self::ROLE_ADMIN],
            ['/admin/rights/user', self::ROLE_SUPER_ADMIN],
        ];
    }
}
