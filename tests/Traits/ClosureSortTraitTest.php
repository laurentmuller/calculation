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
use App\Tests\Entity\IdTrait;
use App\Traits\ClosureSortTrait;
use PHPUnit\Framework\TestCase;

final class ClosureSortTraitTest extends TestCase
{
    use ClosureSortTrait;
    use IdTrait;

    public function testCompareByClosuresReturnZero(): void
    {
        $product1 = $this->createProduct('Description1');
        $product2 = $this->createProduct('Description2', 2);
        $closure = static fn (Product $a, Product $b): int => 0;
        $actual = $this->compareByClosures($product1, $product2, $closure);
        self::assertSame(0, $actual);
    }

    public function testEmptyArray(): void
    {
        $array = [];
        $actual = $this->sortByClosures($array);
        self::assertFalse($actual);
        $actual = $this->sortKeysByClosures($array);
        self::assertFalse($actual);
    }

    public function testOneValue(): void
    {
        $product = $this->createProduct();
        $array = [$product];
        $actual = $this->sortByClosures($array);
        self::assertSame([$product], $array);
        self::assertFalse($actual);
        $actual = $this->sortKeysByClosures($array);
        self::assertSame([$product], $array);
        self::assertFalse($actual);
    }

    public function testSortByClosures(): void
    {
        $product1 = $this->createProduct('Description1');
        $product2 = $this->createProduct('Description2', 2);
        $array = [$product1, $product2];
        $closure = static fn (Product $a, Product $b): int => $a->getId() <=> $b->getId();
        $actual = $this->sortByClosures($array, $closure);
        self::assertSame([$product1, $product2], $array);
        self::assertTrue($actual);
    }

    public function testSortKeysByClosures(): void
    {
        $product1 = $this->createProduct('Description1');
        $product2 = $this->createProduct('Description2', 2);
        $array = [$product1, $product2];
        $closure = static fn (int $a, int $b): int => $a <=> $b;
        $actual = $this->sortKeysByClosures($array, $closure);
        self::assertSame([$product1, $product2], $array);
        self::assertTrue($actual);
    }

    private function createProduct(string $description = 'Description', int $id = 1): Product
    {
        $product = new Product();
        $product->setDescription($description);

        return self::setId($product, $id);
    }
}
