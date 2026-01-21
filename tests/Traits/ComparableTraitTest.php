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

namespace App\Tests\Traits;

use App\Entity\Product;
use App\Traits\ComparableTrait;
use PHPUnit\Framework\TestCase;

final class ComparableTraitTest extends TestCase
{
    use ComparableTrait;

    public function testEmptyArray(): void
    {
        $values = $this->createArray();
        $actual = $this->sortComparable($values);
        self::assertFalse($actual);
        self::assertSame([], $values);

        $values = $this->createArray();
        $actual = $this->sortComparable($values, true);
        self::assertFalse($actual);
        self::assertSame([], $values);
    }

    public function testOneValue(): void
    {
        $product = $this->createProduct('description1');

        $values = $this->createArray($product);
        $actual = $this->sortComparable($values);
        self::assertFalse($actual);
        self::assertSame([$product], $values);

        $values = $this->createArray($product);
        $actual = $this->sortComparable($values, true);
        self::assertFalse($actual);
        self::assertSame([$product], $values);
    }

    public function testSortComparable(): void
    {
        $product1 = $this->createProduct('description1');
        $product2 = $this->createProduct('description2');
        $values = $this->createArray($product1, $product2);
        $actual = $this->sortComparable($values);
        self::assertTrue($actual);
        self::assertSame([$product1, $product2], $values);
    }

    public function testSortComparableReverse(): void
    {
        $product1 = $this->createProduct('description1');
        $product2 = $this->createProduct('description2');
        $values = $this->createArray($product1, $product2);
        $actual = $this->sortComparable($values, true);
        self::assertTrue($actual);
        self::assertSame([1 => $product2, 0 => $product1], $values);
    }

    /**
     * @phpstan-return array<array-key, Product>
     */
    private function createArray(Product ...$products): array
    {
        return [...$products];
    }

    private function createProduct(string $description): Product
    {
        $product = new Product();
        $product->setDescription($description);

        return $product;
    }
}
