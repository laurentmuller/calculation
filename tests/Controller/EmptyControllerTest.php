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
use App\Entity\Product;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for {@link App\Controller\EmptyController} class.
 *
 * @author Laurent Muller
 */
class EmptyControllerTest extends AbstractControllerTest
{
    private static ?Calculation $calculation = null;
    private static ?Category $category = null;
    private static ?Product $product = null;
    private static ?CalculationState $state = null;

    /**
     * {@inheritdoc}
     */
    public function getRoutes(): array
    {
        return [
            ['/empty', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/empty', self::ROLE_ADMIN],
            ['/empty', self::ROLE_SUPER_ADMIN],

            ['/empty/table', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/empty/table', self::ROLE_ADMIN],
            ['/empty/table', self::ROLE_SUPER_ADMIN],

            ['/empty/pdf', self::ROLE_USER, Response::HTTP_FORBIDDEN],
            ['/empty/pdf', self::ROLE_ADMIN],
            ['/empty/pdf', self::ROLE_SUPER_ADMIN],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function addEntities(): void
    {
        if (null === self::$state) {
            self::$state = new CalculationState();
            self::$state->setCode('Test State');
            $this->addEntity(self::$state);
        }

        if (null === self::$category) {
            self::$category = new Category();
            self::$category->setCode('Test Category');
            $this->addEntity(self::$category);
        }

        if (null === self::$product) {
            self::$product = new Product();
            self::$product->setDescription('Test Product')
                ->setCategory(self::$category)
                ->setPrice(0);
            $this->addEntity(self::$product);
        }

        if (null === self::$calculation) {
            self::$calculation = new Calculation();
            self::$calculation->setCustomer('Test Customer')
                ->setDescription('Test Description')
                ->setState(self::$state)
                ->addProduct(self::$product, 0);
            $this->addEntity(self::$calculation);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteEntities(): void
    {
        self::$calculation = $this->deleteEntity(self::$calculation);
        self::$category = $this->deleteEntity(self::$category);
        self::$product = $this->deleteEntity(self::$product);
        self::$state = $this->deleteEntity(self::$state);
    }
}
