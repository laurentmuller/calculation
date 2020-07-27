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

use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Category;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for calculation controller.
 *
 * @author Laurent Muller
 */
class CalculationControllerTest extends AbstractControllerTest
{
    private static ?Calculation $calculation = null;
    private static ?Category $category = null;
    private static ?CalculationState $state = null;

    public function getRoutes(): array
    {
        return [
            ['/calculation', self::ROLE_USER],
            ['/calculation', self::ROLE_ADMIN],
            ['/calculation', self::ROLE_SUPER_ADMIN],

            ['/calculation/table', self::ROLE_USER],
            ['/calculation/table', self::ROLE_ADMIN],
            ['/calculation/table', self::ROLE_SUPER_ADMIN],

            ['/calculation/duplicate', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/calculation/duplicate', self::ROLE_ADMIN],
            ['/calculation/duplicate', self::ROLE_SUPER_ADMIN],

            ['/calculation/duplicate/table', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/calculation/duplicate/table', self::ROLE_ADMIN],
            ['/calculation/duplicate/table', self::ROLE_SUPER_ADMIN],

            ['/calculation/empty', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/calculation/empty', self::ROLE_ADMIN],
            ['/calculation/empty', self::ROLE_SUPER_ADMIN],

            ['/calculation/empty/table', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/calculation/empty/table', self::ROLE_ADMIN],
            ['/calculation/empty/table', self::ROLE_SUPER_ADMIN],

            ['/calculation/add', self::ROLE_USER],
            ['/calculation/add', self::ROLE_ADMIN],
            ['/calculation/add', self::ROLE_SUPER_ADMIN],

            ['/calculation/edit/1', self::ROLE_USER],
            ['/calculation/edit/1', self::ROLE_ADMIN],
            ['/calculation/edit/1', self::ROLE_SUPER_ADMIN],

            ['/calculation/state/1', self::ROLE_USER],
            ['/calculation/state/1', self::ROLE_ADMIN],
            ['/calculation/state/1', self::ROLE_SUPER_ADMIN],

            ['/calculation/delete/1', self::ROLE_USER],
            ['/calculation/delete/1', self::ROLE_ADMIN],
            ['/calculation/delete/1', self::ROLE_SUPER_ADMIN],

            ['/calculation/clone/1', self::ROLE_USER],
            ['/calculation/clone/1', self::ROLE_ADMIN],
            ['/calculation/clone/1', self::ROLE_SUPER_ADMIN],

            ['/calculation/show/1', self::ROLE_USER],
            ['/calculation/show/1', self::ROLE_ADMIN],
            ['/calculation/show/1', self::ROLE_SUPER_ADMIN],

            ['/calculation/pdf', self::ROLE_USER],
            ['/calculation/pdf', self::ROLE_ADMIN],
            ['/calculation/pdf', self::ROLE_SUPER_ADMIN],

//             ['/calculation/pdf/1', self::ROLE_USER],
//             ['/calculation/pdf/1', self::ROLE_ADMIN],
//             ['/calculation/pdf/1', self::ROLE_SUPER_ADMIN],
        ];
    }

    /**
     * @dataProvider getRoutes
     */
    public function testRoutes(string $url, string $username, int $expected = Response::HTTP_OK): void
    {
        // $this->markTestSkipped('Must find errors before to enable.');
        self::addEntities();
        $this->checkRoute($url, $username, $expected);
    }

    private static function addEntities(): void
    {
        if (null === self::$state) {
            self::$state = new CalculationState();
            self::$state->setCode('Test State');
            self::addEntity(self::$state);
        }

        if (null === self::$category) {
            self::$category = new Category();
            self::$category->setCode('Test Category');
            self::addEntity(self::$category);
        }

        if (null === self::$calculation) {
            self::$calculation = new Calculation();
            self::$calculation->setCustomer('Test Customer')
                ->setDescription('Test Description')
                ->setState(self::$state);
            self::addEntity(self::$calculation);
        }
    }

    private static function deleteEntities(): void
    {
        self::$calculation = self::deleteEntity(self::$calculation);
        self::$category = self::deleteEntity(self::$category);
        self::$state = self::deleteEntity(self::$state);
    }
}
