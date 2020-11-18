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
 * Unit test for {@link App\Controller\UpdateController} class.
 *
 * @author Laurent Muller
 */
class UpdateControllerTest extends AbstractControllerTest
{
    private static ?Category $category = null;
    private static $products = null;
    private static ?CalculationState $state = null;

    public function getRoutes(): array
    {
        return [
            ['/update', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/update', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN],
            ['/update', self::ROLE_SUPER_ADMIN],

            ['/update/calculation', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/update/calculation', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN],
            ['/update/calculation', self::ROLE_SUPER_ADMIN, Response::HTTP_FOUND],


            ['/update/customer', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/update/customer', self::ROLE_ADMIN, Response::HTTP_FORBIDDEN],
            ['/update/customer', self::ROLE_SUPER_ADMIN, Response::HTTP_FOUND],
        ];
    }

    protected function addEntities(): void
    {
        if (null === self::$state) {
            self::$state = new CalculationState();
            self::$state->setCode('Test Code');
            $this->addEntity(self::$state);
        }

        if (null === self::$category) {
            self::$category = new Category();
            self::$category->setCode('Test Category');
            $this->addEntity(self::$category);
        }

        if (null === self::$products) {
            for ($i = 0; $i < 15; ++$i) {
                $product = new Product();
                $product->setDescription("Test Product $i")
                    ->setCategory(self::$category);
                $this->addEntity($product);
                self::$products[] = $product;
            }
        }
    }

    protected function deleteEntities(): void
    {
        foreach (self::$products as $product) {
            $this->deleteEntity($product);
        }
        self::$products = null;

        self::$category = $this->deleteEntity(self::$category);
        self::$state = $this->deleteEntity(self::$state);
    }
}
