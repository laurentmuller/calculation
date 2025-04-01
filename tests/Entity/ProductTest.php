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

class ProductTest extends EntityValidatorTestCase
{
    /**
     * @psalm-return \Generator<int, array{float, float}>
     */
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
        $product->setDescription('product');

        self::assertNull($product->getCategory());
        self::assertSame('', $product->getCategoryCode());
        self::assertNull($product->getCategoryId());

        self::assertNull($product->getGroup());
        self::assertSame('', $product->getGroupCode());

        $group = $this->getGroup();
        $category = $this->getCategory($group);
        $product->setCategory($category);

        self::assertNotNull($product->getCategory());
        self::assertNotNull($product->getCategoryCode());
        self::assertSame('category', $product->getCategoryCode());

        self::assertNotNull($product->getGroup());
        self::assertNotNull($product->getGroupCode());
        self::assertSame('group', $product->getGroupCode());
    }

    public function testClone(): void
    {
        $product = new Product();
        $product->setDescription('product');

        $clone = $product->clone();
        self::assertSame($product->getDescription(), $clone->getDescription());

        $clone = $product->clone('new-product');
        self::assertNotSame($product->getDescription(), $clone->getDescription());
        self::assertSame('new-product', $clone->getDescription());
    }

    public function testCompare(): void
    {
        $item1 = new Product();
        $item1->setDescription('Product1');
        $item2 = new Product();
        $item2->setDescription('Product2');
        $actual = $item1->compare($item2);
        self::assertSame(-1, $actual);
    }

    public function testDuplicate(): void
    {
        $group = $this->getGroup();
        $category = $this->getCategory($group);
        $first = new Product();
        $first->setDescription('My Product')
            ->setCategory($category);

        try {
            $this->saveEntity($group);
            $this->saveEntity($category);
            $this->saveEntity($first);
            $second = new Product();
            $second->setDescription('My Product')
                ->setCategory($category);
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
        $product->setDescription('My Product');
        $results = $this->validate($product, 1);
        $this->validatePaths($results, 'category');
    }

    public function testInvalidDescription(): void
    {
        $group = $this->getGroup();
        $category = $this->getCategory($group);
        $product = new Product();
        $product->setCategory($category);
        $results = $this->validate($product, 1);
        $this->validatePaths($results, 'description');
    }

    public function testNotDuplicate(): void
    {
        $group = $this->getGroup();
        $category = $this->getCategory($group);
        $category->setGroup($group);
        $first = new Product();
        $first->setDescription('My Product')
            ->setCategory($category);

        try {
            $this->saveEntity($group);
            $this->saveEntity($category);
            $this->saveEntity($first);
            $second = new Product();
            $second->setDescription('My Product 2')
                ->setCategory($category);
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
        $product->setPrice($price);
        self::assertSame($expected, $product->getPrice());
    }

    public function testSupplier(): void
    {
        $product = new Product();
        self::assertNull($product->getSupplier());
        $product->setSupplier('supplier');
        self::assertSame('supplier', $product->getSupplier());
    }

    public function testUnit(): void
    {
        $product = new Product();
        self::assertNull($product->getUnit());
        $product->setUnit('unit');
        self::assertSame('unit', $product->getUnit());
    }

    public function testValid(): void
    {
        $group = $this->getGroup();
        $category = $this->getCategory($group);
        $product = new Product();
        $product->setDescription('product')
            ->setCategory($category);
        $this->validate($product);
    }

    private function getCategory(Group $group): Category
    {
        $category = new Category();
        $category->setCode('category');
        $category->setGroup($group);

        return $category;
    }

    private function getGroup(): Group
    {
        $group = new Group();
        $group->setCode('group');

        return $group;
    }
}
