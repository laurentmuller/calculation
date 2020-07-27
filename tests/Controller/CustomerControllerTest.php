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

use App\Entity\Customer;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for customer controller.
 *
 * @author Laurent Muller
 */
class CustomerControllerTest extends AbstractControllerTest
{
    private static ?Customer $entity = null;

    public function getRoutes(): array
    {
        return [
            ['/customer', self::ROLE_USER],
            ['/customer', self::ROLE_ADMIN],
            ['/customer', self::ROLE_SUPER_ADMIN],

            ['/customer/table', self::ROLE_USER],
            ['/customer/table', self::ROLE_ADMIN],
            ['/customer/table', self::ROLE_SUPER_ADMIN],

            ['/customer/add', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/customer/add', self::ROLE_ADMIN],
            ['/customer/add', self::ROLE_SUPER_ADMIN],

            ['/customer/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/customer/edit/1', self::ROLE_ADMIN],
            ['/customer/edit/1', self::ROLE_SUPER_ADMIN],

            ['/customer/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/customer/delete/1', self::ROLE_ADMIN],
            ['/customer/delete/1', self::ROLE_SUPER_ADMIN],

            ['/customer/show/1', self::ROLE_USER],
            ['/customer/show/1', self::ROLE_ADMIN],
            ['/customer/show/1', self::ROLE_SUPER_ADMIN],

            ['/customer/pdf', self::ROLE_USER],
            ['/customer/pdf', self::ROLE_ADMIN],
            ['/customer/pdf', self::ROLE_SUPER_ADMIN],
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
            self::$entity = new Customer();
            self::$entity->setCompany('Test Company');
            self::addEntity(self::$entity);
        }
    }

    private static function deleteEntities(): void
    {
        self::$entity = self::deleteEntity(self::$entity);
    }
}
