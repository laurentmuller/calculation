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

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for {@link App\Controller\UserController} class.
 *
 * @author Laurent Muller
 */
class UserControllerTest extends AbstractControllerTest
{
    public function getRoutes(): array
    {
        return [
            ['/user', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/user', self::ROLE_ADMIN],
            ['/user', self::ROLE_SUPER_ADMIN],

            ['/user/table', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/user/table', self::ROLE_ADMIN],
            ['/user/table', self::ROLE_SUPER_ADMIN],

            ['/user/add', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/user/add', self::ROLE_ADMIN],
            ['/user/add', self::ROLE_SUPER_ADMIN],

            ['/user/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/user/edit/1', self::ROLE_ADMIN],
            ['/user/edit/1', self::ROLE_SUPER_ADMIN],

            ['/user/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/user/delete/1', self::ROLE_ADMIN],
            // can delete when connected
            ['/user/delete/1', self::ROLE_SUPER_ADMIN, Response::HTTP_FOUND],
            ['/user/delete/2', self::ROLE_SUPER_ADMIN],

            ['/user/show/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/user/show/1', self::ROLE_ADMIN],
            ['/user/show/1', self::ROLE_SUPER_ADMIN],

            ['/user/image/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/user/image/1', self::ROLE_ADMIN],
            ['/user/image/1', self::ROLE_SUPER_ADMIN],

            ['/user/password/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/user/password/1', self::ROLE_ADMIN],
            ['/user/password/1', self::ROLE_SUPER_ADMIN],

            ['/user/rights/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/user/rights/1', self::ROLE_ADMIN],
            ['/user/rights/1', self::ROLE_SUPER_ADMIN],

            ['/user/theme', self::ROLE_USER],
            ['/user/theme', self::ROLE_ADMIN],
            ['/user/theme', self::ROLE_SUPER_ADMIN],

            ['/user/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/user/pdf', self::ROLE_ADMIN],
            ['/user/pdf', self::ROLE_SUPER_ADMIN],

            ['/user/excel', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/user/excel', self::ROLE_ADMIN],
            ['/user/excel', self::ROLE_SUPER_ADMIN],
        ];
    }
}
