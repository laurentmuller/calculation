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

class ComparableTraitTest extends TestCase
{
    use ComparableTrait;

    public function testComparableSorted(): void
    {
        $values = [];
        $values[] = $item1 = $this->createProduct('description1');
        $values[] = $item2 = $this->createProduct('description2');

        $actual = $this->getSortedComparable($values);
        self::assertSame([$item1, $item2], $actual);
    }

    public function testEmptyArray(): void
    {
        $values = [];

        $actual = $this->getSortedComparable($values);
        self::assertSame([], $actual);

        $actual = $this->getReversedSortedComparable($values);
        self::assertSame([], $actual);
    }

    public function testReversedSortedComparableNoPreserveKey(): void
    {
        $values = [];
        $values[] = $item1 = $this->createProduct('description1');
        $values[] = $item2 = $this->createProduct('description2');

        $actual = $this->getReversedSortedComparable($values, false);
        self::assertSame([$item2, $item1], $actual);
    }

    public function testReversedSortedComparablePreserveKey(): void
    {
        $values = [];
        $values[] = $item1 = $this->createProduct('description1');
        $values[] = $item2 = $this->createProduct('description2');

        $actual = $this->getReversedSortedComparable($values);
        self::assertSame([1 => $item2, 0 => $item1], $actual);
    }

    private function createProduct(string $description): Product
    {
        $product = new Product();

        return $product->setDescription($description);
    }
}
