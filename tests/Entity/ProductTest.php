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

namespace App\Tests\Entity;

use App\Entity\Category;
use App\Entity\Product;

/**
 * Unit test for validate product constraints.
 *
 * @author Laurent Muller
 */
class ProductTest extends EntityValidatorTest
{
    public function testDuplicate(): void
    {
        $category = $this->getCategory();

        $first = new Product();
        $first->setCategory($category)
            ->setDescription('My Product');

        try {
            $this->saveEntity($category);
            $this->saveEntity($first);

            $second = new Product();
            $second->setCategory($category)
                ->setDescription('My Product');

            $this->validate($second, 1);
        } finally {
            $this->deleteEntity($first);
            $this->deleteEntity($category);
        }
    }

    public function testInvalidBoth(): void
    {
        $product = new Product();

        $result = $this->validator->validate($product);
        $this->assertSame(2, $result->count());
    }

    public function testInvalidCategory(): void
    {
        $product = new Product();
        $product->setDescription('My Product');
        $this->validate($product, 1);
    }

    public function testInvalidDescription(): void
    {
        $product = new Product();
        $product->setCategory($this->getCategory());
        $this->validate($product, 1);
    }

    public function testNotDuplicate(): void
    {
        $category = $this->getCategory();

        $first = new Product();
        $first->setCategory($category)
            ->setDescription('My Product');

        try {
            $this->saveEntity($category);
            $this->saveEntity($first);

            $second = new Product();
            $second->setCategory($category)
                ->setDescription('My Product 2');

            $this->validate($second, 0);
        } finally {
            $this->deleteEntity($first);
            $this->deleteEntity($category);
        }
    }

    public function testValid(): void
    {
        $product = new Product();
        $product->setCategory($this->getCategory())
            ->setDescription('My Product');
        $this->validate($product, 0);
    }

    private function getCategory(): Category
    {
        $category = new Category();
        $category->setCode('mycode');

        return $category;
    }
}
