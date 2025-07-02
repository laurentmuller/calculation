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

use App\Model\ProductUpdateResult;
use PHPUnit\Framework\TestCase;

/**
 * @phpstan-import-type ProductType from ProductUpdateResult
 */
class ProductUpdateResultTest extends TestCase
{
    public function testAddProduct(): void
    {
        $result = new ProductUpdateResult();
        $product = $this->createProduct();
        $result->addProduct($product);
        self::assertCount(1, $result);
        $products = $result->getProducts();
        self::assertArrayHasKey(0, $products);
        $actual = $products[0];
        self::assertSame($product, $actual);
    }

    public function testCount(): void
    {
        $actual = new ProductUpdateResult();
        self::assertCount(0, $actual);
    }

    public function testIsValid(): void
    {
        $result = new ProductUpdateResult();
        self::assertFalse($result->isValid());
        $product = $this->createProduct();
        $result->addProduct($product);
        self::assertTrue($result->isValid());
    }

    /**
     * @phpstan-return ProductType
     */
    private function createProduct(): array
    {
        return [
            'description' => 'description',
            'oldPrice' => 1.5,
            'newPrice' => 2.0,
            'delta' => 0.5,
        ];
    }
}
