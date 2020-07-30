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

use App\Entity\Category;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for category controller.
 *
 * @author Laurent Muller
 */
class CategoryControllerTest extends AbstractControllerTest
{
    private static ?Category $entity = null;

    public function getRoutes(): array
    {
        return [
            ['/category', self::ROLE_USER],
            ['/category', self::ROLE_ADMIN],
            ['/category', self::ROLE_SUPER_ADMIN],

            ['/category/table', self::ROLE_USER],
            ['/category/table', self::ROLE_ADMIN],
            ['/category/table', self::ROLE_SUPER_ADMIN],

            ['/category/add', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/category/add', self::ROLE_ADMIN],
            ['/category/add', self::ROLE_SUPER_ADMIN],

            ['/category/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/category/edit/1', self::ROLE_ADMIN],
            ['/category/edit/1', self::ROLE_SUPER_ADMIN],

            ['/category/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/category/delete/1', self::ROLE_ADMIN],
            ['/category/delete/1', self::ROLE_SUPER_ADMIN],

            ['/category/show/1', self::ROLE_USER],
            ['/category/show/1', self::ROLE_ADMIN],
            ['/category/show/1', self::ROLE_SUPER_ADMIN],

            ['/category/pdf', self::ROLE_USER],
            ['/category/pdf', self::ROLE_ADMIN],
            ['/category/pdf', self::ROLE_SUPER_ADMIN],
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
            self::$entity = new Category();
            self::$entity->setCode('Test Code');
            self::addEntity(self::$entity);
        }
    }

    private static function deleteEntities(): void
    {
        self::$entity = self::deleteEntity(self::$entity);
    }
}
