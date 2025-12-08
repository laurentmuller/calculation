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

namespace App\Tests\Model;

use App\Entity\Category;
use App\Entity\Group;
use App\Entity\Product;
use App\Model\ProductUpdateQuery;
use App\Tests\Entity\IdTrait;
use PHPUnit\Framework\TestCase;

final class ProductUpdateQueryTest extends TestCase
{
    use IdTrait;

    public function testAllProducts(): void
    {
        $query = new ProductUpdateQuery();
        self::assertTrue($query->isAllProducts());
        $query->setAllProducts(false);
        self::assertFalse($query->isAllProducts());
    }

    public function testFixed(): void
    {
        $query = new ProductUpdateQuery();
        self::assertSame(0.0, $query->getFixed());
        $query->setFixed(2.0);
        self::assertSame(2.0, $query->getFixed());
    }

    public function testFormattedValue(): void
    {
        $query = new ProductUpdateQuery();
        $query->setPercent(1.0);
        self::assertSame('100%', $query->getFormattedValue());
        $query->setType(ProductUpdateQuery::UPDATE_FIXED);
        $query->setFixed(1.0);
        self::assertSame('1.00', $query->getFormattedValue());
    }

    public function testGetCategory(): void
    {
        $query = new ProductUpdateQuery();
        self::assertNull($query->getCategory());
        self::assertSame(0, $query->getCategoryId());
        self::assertNull($query->getCategoryCode());
        self::assertNull($query->getGroupCode());

        $category = $this->createCategory();
        $query->setCategory($category);
        self::assertNotNull($query->getCategory());
        self::assertSame($category, $query->getCategory());
        self::assertSame(1, $query->getCategoryId());
        self::assertSame('code', $query->getCategoryCode());
        self::assertSame('group', $query->getGroupCode());
    }

    public function testGetProducts(): void
    {
        $query = new ProductUpdateQuery();
        self::assertEmpty($query->getProducts());
        self::assertSame([], $query->getProducts());

        $products = [$this->createProduct()];
        $query->setProducts($products);
        self::assertSame($products, $query->getProducts());
    }

    public function testIsPercent(): void
    {
        $query = new ProductUpdateQuery();
        self::assertTrue($query->isPercent());
        self::assertFalse($query->isFixed());
        $query->setType(ProductUpdateQuery::UPDATE_FIXED);
        self::assertFalse($query->isPercent());
        self::assertTrue($query->isFixed());
    }

    public function testIsRound(): void
    {
        $query = new ProductUpdateQuery();
        self::assertFalse($query->isRound());
        $query->setRound(true);
        self::assertTrue($query->isRound());
    }

    public function testPercent(): void
    {
        $query = new ProductUpdateQuery();
        self::assertSame(0.0, $query->getPercent());
        $query->setPercent(2.0);
        self::assertSame(2.0, $query->getPercent());
    }

    public function testType(): void
    {
        $query = new ProductUpdateQuery();
        self::assertSame(ProductUpdateQuery::UPDATE_PERCENT, $query->getType());
        $query->setType(ProductUpdateQuery::UPDATE_FIXED);
        self::assertSame(ProductUpdateQuery::UPDATE_FIXED, $query->getType());
    }

    private function createCategory(): Category
    {
        $group = new Group();
        $group->setCode('group');
        self::setId($group);

        $category = new Category();
        $category->setCode('code')
            ->setGroup($group);

        return self::setId($category);
    }

    private function createProduct(): Product
    {
        $product = new Product();
        $product->setCategory($this->createCategory())
            ->setDescription('description')
            ->setPrice(1.0);

        return self::setId($product);
    }
}
