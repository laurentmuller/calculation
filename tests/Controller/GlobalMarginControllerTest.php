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

use App\Entity\GlobalMargin;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for global margin controller.
 *
 * @author Laurent Muller
 */
class GlobalMarginControllerTest extends AbstractControllerTest
{
    private static ?GlobalMargin $entity = null;

    public function getRoutes(): array
    {
        return [
            ['/globalmargin', self::ROLE_USER],
            ['/globalmargin', self::ROLE_ADMIN],
            ['/globalmargin', self::ROLE_SUPER_ADMIN],

            ['/globalmargin/table', self::ROLE_USER],
            ['/globalmargin/table', self::ROLE_ADMIN],
            ['/globalmargin/table', self::ROLE_SUPER_ADMIN],

            ['/globalmargin/add', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/globalmargin/add', self::ROLE_ADMIN],
            ['/globalmargin/add', self::ROLE_SUPER_ADMIN],

            ['/globalmargin/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/globalmargin/edit/1', self::ROLE_ADMIN],
            ['/globalmargin/edit/1', self::ROLE_SUPER_ADMIN],

            ['/globalmargin/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/globalmargin/delete/1', self::ROLE_ADMIN],
            ['/globalmargin/delete/1', self::ROLE_SUPER_ADMIN],

            ['/globalmargin/show/1', self::ROLE_USER],
            ['/globalmargin/show/1', self::ROLE_ADMIN],
            ['/globalmargin/show/1', self::ROLE_SUPER_ADMIN],
        ];
    }

    /**
     * @dataProvider getRoutes
     */
    public function testRoutes(string $url, string $username, int $expected = Response::HTTP_OK): void
    {
        self::addEntities();
        $this->checkRoute($url, $username, $expected);
    }

    private static function addEntities(): void
    {
        if (null === self::$entity) {
            self::$entity = new GlobalMargin();
            self::$entity->setValues(0.0, 100.0, 0.1);
            self::addEntity(self::$entity);
        }
    }

    private static function deleteEntities(): void
    {
        self::$entity = self::deleteEntity(self::$entity);
    }
}
