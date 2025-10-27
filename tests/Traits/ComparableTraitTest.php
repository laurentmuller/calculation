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
        $values = $this->sortComparable($this->createArray());
        self::assertSame([], $values);

        $values = $this->sortReverseComparable($this->createArray());
        self::assertSame([], $values);
    }

    public function testOneValue(): void
    {
        $product = $this->createProduct('description1');

        $values = $this->sortComparable($this->createArray($product));
        self::assertSame([$product], $values);

        $values = $this->sortReverseComparable($this->createArray($product));
        self::assertSame([$product], $values);
    }

    public function testSortComparable(): void
    {
        $product1 = $this->createProduct('description1');
        $product2 = $this->createProduct('description2');

        $values = $this->sortComparable($this->createArray($product1, $product2));
        self::assertSame([$product1, $product2], $values);
    }

    public function testSortReverseComparable(): void
    {
        $product1 = $this->createProduct('description1');
        $product2 = $this->createProduct('description2');

        $values = $this->sortReverseComparable($this->createArray($product1, $product2));
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
