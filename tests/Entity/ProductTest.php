<?php

/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Category;
use App\Entity\Group;
use App\Entity\Product;
use PHPUnit\Framework\Attributes\DataProvider;

final class ProductTest extends EntityValidatorTestCase
{
    public static function getPrices(): \Generator
    {
        yield [1.245, 1.24];
        yield [1.246, 1.25];
        yield [1.249, 1.25];

        yield [1.25, 1.25];

        yield [1.251, 1.25];
        yield [1.255, 1.25];
        yield [1.256, 1.26];
    }

    public function testCategoryAndGroup(): void
    {
        $product = new Product();
        $product->withDescription('product');

        self::assertNull($product->category);
        self::assertSame('', $product->getCategoryCode());
        self::assertNull($product->getCategoryId());

        self::assertNull($product->getGroup());
        self::assertSame('', $product->getGroupCode());

        $group = $this->getGroup();
        $category = $this->getCategory($group);
        $product->withCategory($category);

        self::assertNotNull($product->category);
        self::assertNotNull($product->getCategoryCode());
        self::assertSame('category', $product->getCategoryCode());

        self::assertNotNull($product->getGroup());
        self::assertNotNull($product->getGroupCode());
        self::assertSame('group', $product->getGroupCode());
    }

    public function testClone(): void
    {
        $product = new Product();
        $product->withDescription('product');

        $clone = $product->clone();
        self::assertSame($product->description, $clone->description);

        $clone = $product->clone('new-product');
        self::assertNotSame($product->description, $clone->description);
        self::assertSame('new-product', $clone->description);
    }

    public function testCompare(): void
    {
        $item1 = new Product();
        $item1->withDescription('Product1');
        $item2 = new Product();
        $item2->withDescription('Product2');
        $actual = $item1->compare($item2);
        self::assertSame(-1, $actual);
    }

    public function testDuplicate(): void
    {
        $group = $this->getGroup();
        $category = $this->getCategory($group);
        $first = new Product();
        $first->withDescription('My Product')
            ->withCategory($category);

        try {
            $this->saveEntity($group);
            $this->saveEntity($category);
            $this->saveEntity($first);
            $second = new Product();
            $second->withDescription('My Product')
                ->withCategory($category);
            $results = $this->validate($second, 1);
            $this->validatePaths($results, 'description');
        } finally {
            $this->deleteEntity($first);
            $this->deleteEntity($category);
            $this->deleteEntity($group);
        }
    }

    public function testInvalidBoth(): void
    {
        $product = new Product();
        $results = $this->validate($product, 2);
        $this->validatePaths($results, 'category', 'description');
    }

    public function testInvalidCategory(): void
    {
        $product = new Product();
        $product->withDescription('My Product');
        $results = $this->validate($product, 1);
        $this->validatePaths($results, 'category');
    }

    public function testInvalidDescription(): void
    {
        $group = $this->getGroup();
        $category = $this->getCategory($group);
        $product = new Product();
        $product->withCategory($category);
        $results = $this->validate($product, 1);
        $this->validatePaths($results, 'description');
    }

    public function testNotDuplicate(): void
    {
        $group = $this->getGroup();
        $category = $this->getCategory($group);
        $category->withGroup($group);
        $first = new Product();
        $first->withDescription('My Product')
            ->withCategory($category);

        try {
            $this->saveEntity($group);
            $this->saveEntity($category);
            $this->saveEntity($first);
            $second = new Product();
            $second->withDescription('My Product 2')
                ->withCategory($category);
            $this->validate($second);
        } finally {
            $this->deleteEntity($first);
            $this->deleteEntity($category);
            $this->deleteEntity($group);
        }
    }

    #[DataProvider('getPrices')]
    public function testPrice(float $price, float $expected): void
    {
        $product = new Product();
        $product->withPrice($price);
        self::assertSame($expected, $product->price);
    }

    public function testSupplier(): void
    {
        $product = new Product();
        self::assertNull($product->supplier);
        $product->withSupplier('supplier');
        self::assertSame('supplier', $product->supplier);
    }

    public function testUnit(): void
    {
        $product = new Product();
        self::assertNull($product->unit);
        $product->withUnit('unit');
        self::assertSame('unit', $product->unit);
    }

    public function testValid(): void
    {
        $group = $this->getGroup();
        $category = $this->getCategory($group);
        $product = new Product();
        $product->withDescription('product')
            ->withCategory($category);
        $this->validate($product);
    }

    private function getCategory(Group $group): Category
    {
        $category = new Category();
        $category->withCode('category');
        $category->withGroup($group);

        return $category;
    }

    private function getGroup(): Group
    {
        $group = new Group();
        $group->withCode('group');

        return $group;
    }
}
