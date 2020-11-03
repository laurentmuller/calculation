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
 * Unit test for {@link App\Controller\AdminController} class.
 *
 * @author Laurent Muller
 */
class AdminControllerTest extends AbstractControllerTest
{
    public function getRoutes(): array
    {
        return [
            ['/admin/clear', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/admin/clear', self::ROLE_ADMIN],
            ['/admin/clear', self::ROLE_SUPER_ADMIN],

            ['/admin/import', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/admin/import', self::ROLE_ADMIN],
            ['/admin/import', self::ROLE_SUPER_ADMIN],

            ['/admin/parameters', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/admin/parameters', self::ROLE_ADMIN],
            ['/admin/parameters', self::ROLE_SUPER_ADMIN],

            ['/admin/rights/admin', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/admin/rights/admin', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN],
            ['/admin/rights/admin', self::ROLE_SUPER_ADMIN],

            ['/admin/rights/user', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/admin/rights/user', self::ROLE_ADMIN],
            ['/admin/rights/user', self::ROLE_SUPER_ADMIN],

            ['/admin/update', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/admin/update', self::ROLE_ADMIN],
            ['/admin/update', self::ROLE_SUPER_ADMIN],
        ];
    }
}
