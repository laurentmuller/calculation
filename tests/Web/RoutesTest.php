<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Tests\Web;

use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for users and routes.
 *
 * @author Laurent Muller
 */
class RoutesTest extends AuthenticateWebTestCase
{
    public function getRoutes(): array
    {
        return [
            ['/', self::ROLE_USER, Response::HTTP_OK],
            ['/', self::ROLE_ADMIN, Response::HTTP_OK],
            ['/', self::ROLE_SUPER_ADMIN, Response::HTTP_OK],

            // about controller
            ['/about', self::ROLE_USER, Response::HTTP_OK],
            ['/about', self::ROLE_ADMIN, Response::HTTP_OK],
            ['/about', self::ROLE_SUPER_ADMIN, Response::HTTP_OK],

            // admin controller
            ['/admin/rights/admin', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/admin/rights/admin', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN],
            ['/admin/rights/admin', self::ROLE_SUPER_ADMIN, Response::HTTP_OK],

            ['/admin/rights/user', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/admin/rights/user', self::ROLE_ADMIN, Response::HTTP_OK],
            ['/admin/rights/user', self::ROLE_SUPER_ADMIN, Response::HTTP_OK],

            ['/admin/parameters', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/admin/parameters', self::ROLE_ADMIN, Response::HTTP_OK],
            ['/admin/parameters', self::ROLE_SUPER_ADMIN, Response::HTTP_OK],

            // not exist
            ['/not_exist', self::ROLE_USER, Response::HTTP_NOT_FOUND],
        ];
    }

    public function getUsers(): array
    {
        return [
            [self::ROLE_USER, true],
            [self::ROLE_ADMIN, true],
            [self::ROLE_SUPER_ADMIN, true],
            [self::ROLE_DISABLED, true],
            [self::ROLE_FAKE, false],
        ];
    }

    /**
     * @dataProvider getRoutes
     */
    public function testRoutes(string $url, string $username, int $expected): void
    {
        $this->doEcho('URL', $url);

        $user = $this->loadUser($username);
        $this->loginUser($user);

        $this->client->request('GET', $url);
        $response = $this->client->getResponse();
        $statusCode = $response->getStatusCode();
        $this->doEcho('StatusCode', "$expected => $statusCode");

        $this->assertSame($expected, $statusCode, "Invalid status code for '{$url}' with the user '{$user}'.");
    }

    /**
     * @dataProvider getUsers
     */
    public function testUsers(string $username, bool $exist): void
    {
        $user = $this->loadUser($username, false);
        if ($exist) {
            $this->assertNotNull($user, "The user '$username' is null.");
        } else {
            $this->assertNull($user, "The user '$username' is not null.");
        }
    }
}
