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

namespace App\Tests\Web;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for users and routes.
 */
class RoutesTest extends AbstractAuthenticateWebTestCase
{
    /**
     * @return array<int, array<int, int|string>>
     */
    public function getRoutes(): array
    {
        return [
            // index
            ['/', self::ROLE_USER],
            ['/', self::ROLE_ADMIN],
            ['/', self::ROLE_SUPER_ADMIN],

            // about controller
            ['/about', self::ROLE_USER],
            ['/about', self::ROLE_ADMIN],
            ['/about', self::ROLE_SUPER_ADMIN],

            // admin controller
            ['/admin/rights/admin', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/admin/rights/admin', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN],
            ['/admin/rights/admin', self::ROLE_SUPER_ADMIN],

            ['/admin/rights/user', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/admin/rights/user', self::ROLE_ADMIN],
            ['/admin/rights/user', self::ROLE_SUPER_ADMIN],

            ['/admin/parameters', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/admin/parameters', self::ROLE_ADMIN],
            ['/admin/parameters', self::ROLE_SUPER_ADMIN],

            // not exist
            ['/not_exist', self::ROLE_USER, Response::HTTP_NOT_FOUND],
        ];
    }

    /**
     * @dataProvider getRoutes
     */
    public function testRoutes(string $url, string $username, int $expected = Response::HTTP_OK): void
    {
        $this->loginUserName($username);
        $this->client->request(Request::METHOD_GET, $url);
        $this->checkResponse($url, $username, $expected);
    }
}
