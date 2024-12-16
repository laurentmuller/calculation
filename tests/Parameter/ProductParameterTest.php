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

namespace App\Tests\Parameter;

use App\Entity\Product;
use App\Parameter\ProductParameter;

/**
 * @extends AbstractParameterTestCase<ProductParameter>
 */
class ProductParameterTest extends AbstractParameterTestCase
{
    public static function getParameterNames(): \Generator
    {
        yield ['edit', 'default_product_edit'];
        yield ['product', 'default_product'];
        yield ['quantity', 'default_product_quantity'];
    }

    public static function getParameterValues(): \Generator
    {
        yield ['edit', false];
        yield ['product', null];
        yield ['quantity', 0.0];
    }

    public function testDefaultValue(): void
    {
        self::assertFalse($this->parameter->isEdit());
        self::assertNull($this->parameter->getProduct());
        self::assertSame(0.0, $this->parameter->getQuantity());

        self::assertSame('parameter_product', $this->parameter::getCacheKey());
    }

    public function testSetValue(): void
    {
        $product = new Product();

        $this->parameter->setEdit(true);
        self::assertTrue($this->parameter->isEdit());
        $this->parameter->setProduct($product);
        self::assertSame($product, $this->parameter->getProduct());
        $this->parameter->setQuantity(0.25);
        self::assertSame(0.25, $this->parameter->getQuantity());
    }

    protected function createParameter(): ProductParameter
    {
        return new ProductParameter();
    }
}
