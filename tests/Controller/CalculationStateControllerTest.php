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

use App\Entity\CalculationState;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for calculation state controller.
 *
 * @author Laurent Muller
 */
class CalculationStateControllerTest extends AbstractControllerTest
{
    private static ?CalculationState $entity = null;

    public function getRoutes(): array
    {
        return [
            ['/calculationstate', self::ROLE_USER],
            ['/calculationstate', self::ROLE_ADMIN],
            ['/calculationstate', self::ROLE_SUPER_ADMIN],

            ['/calculationstate/table', self::ROLE_USER],
            ['/calculationstate/table', self::ROLE_ADMIN],
            ['/calculationstate/table', self::ROLE_SUPER_ADMIN],

            ['/calculationstate/add', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/calculationstate/add', self::ROLE_ADMIN],
            ['/calculationstate/add', self::ROLE_SUPER_ADMIN],

            ['/calculationstate/edit/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/calculationstate/edit/1', self::ROLE_ADMIN],
            ['/calculationstate/edit/1', self::ROLE_SUPER_ADMIN],

            ['/calculationstate/delete/1', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/calculationstate/delete/1', self::ROLE_ADMIN],
            ['/calculationstate/delete/1', self::ROLE_SUPER_ADMIN],

            ['/calculationstate/show/1', self::ROLE_USER],
            ['/calculationstate/show/1', self::ROLE_ADMIN],
            ['/calculationstate/show/1', self::ROLE_SUPER_ADMIN],
        ];
    }

    /**
     * @dataProvider getRoutes
     */
    public function testRoutes(string $url, string $username, int $expected = Response::HTTP_OK): void
    {
        self::addEntities();
        $this->assertNotNull(self::$entity);
        $this->assertEquals(1, self::$entity->getId());
        $this->checkRoute($url, $username, $expected);
    }

    private static function addEntities(): void
    {
        if (null === self::$entity) {
            self::$entity = new CalculationState();
            self::$entity->setCode('Test Code');
            self::addEntity(self::$entity);
        }
    }

    private static function deleteEntities(): void
    {
        self::$entity = self::deleteEntity(self::$entity);
    }
}
