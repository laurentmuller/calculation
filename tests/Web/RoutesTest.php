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

use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for users and routes.
 *
 * @author Laurent Muller
 */
class RoutesTest extends AuthenticateWebTestCase
{
    private const ROLE_ADMIN = User::ROLE_ADMIN;
    private const ROLE_USER = User::ROLE_DEFAULT;
    private const ROLE_DISABLED = 'ROLE_DISABLED';
    private const ROLE_FAKE = 'ROLE_FAKE';
    private const ROLE_SUPER_ADMIN = User::ROLE_SUPER_ADMIN;

    public function getRoutes(): array
    {
        return [
            ['/', self::ROLE_USER, Response::HTTP_OK],
            ['/', self::ROLE_ADMIN, Response::HTTP_OK],
            ['/', self::ROLE_SUPER_ADMIN, Response::HTTP_OK],

            ['/about', self::ROLE_USER, Response::HTTP_OK],
            ['/about', self::ROLE_ADMIN, Response::HTTP_OK],
            ['/about', self::ROLE_SUPER_ADMIN, Response::HTTP_OK],

            ['/admin/parameters', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/admin/parameters', self::ROLE_ADMIN, Response::HTTP_OK],
            ['/admin/parameters', self::ROLE_SUPER_ADMIN, Response::HTTP_OK],

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
        // echo "\n";

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
