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
use App\Entity\Category;
use App\Entity\Product;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for generator controller.
 *
 * @author Laurent Muller
 */
class GeneratorControllerTest extends AbstractControllerTest
{
    private static ?Category $category = null;
    private static $products = null;
    private static ?CalculationState $state = null;

    public function getRoutes(): array
    {
        return [
            ['/generate', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/generate', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN],
            ['/generate', self::ROLE_SUPER_ADMIN],

            ['/calculation/generate', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/calculation/generate', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN],
            ['/calculation/generate', self::ROLE_SUPER_ADMIN],

            ['/calculation/update', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/calculation/update', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN],
            ['/calculation/update', self::ROLE_SUPER_ADMIN, Response::HTTP_FOUND],

            ['/customer/generate', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/customer/generate', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN],
            ['/customer/generate', self::ROLE_SUPER_ADMIN],

            ['/customer/update', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/customer/update', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN],
            ['/customer/update', self::ROLE_SUPER_ADMIN, Response::HTTP_FOUND],
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
        if (null === self::$state) {
            self::$state = new CalculationState();
            self::$state->setCode('Test Code');
            self::addEntity(self::$state);
        }

        if (null === self::$category) {
            self::$category = new Category();
            self::$category->setCode('Test Category');
            self::addEntity(self::$category);
        }

        if (null === self::$products) {
            for ($i = 0; $i < 15; ++$i) {
                $product = new Product();
                $product->setDescription("Test Product $i")
                    ->setCategory(self::$category);
                self::addEntity($product);
                self::$products[] = $product;
            }
        }
    }

    private static function deleteEntities(): void
    {
        foreach (self::$products as $product) {
            self::deleteEntity($product);
        }
        self::$products = null;

        self::$category = self::deleteEntity(self::$category);
        self::$state = self::deleteEntity(self::$state);
    }
}
