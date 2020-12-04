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
 * Unit test for {@link App\Controller\ImportProductController} class.
 *
 * @author Laurent Muller
 */
class ImportProductControllerTest extends AbstractControllerTest
{
    public function getRoutes(): array
    {
        return [
            ['/product/import', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/product/import', self::ROLE_ADMIN],
            ['/product/import', self::ROLE_SUPER_ADMIN],
        ];
    }
}
