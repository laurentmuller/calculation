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
use App\Traits\CollectionTrait;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

class CollectionTraitTest extends TestCase
{
    use CollectionTrait;

    public function testComparableSorted(): void
    {
        /** @psalm-var ArrayCollection<array-key, Product> $collection */
        $collection = new ArrayCollection();
        $item1 = $this->createProduct('description1');
        $collection->add($item1);
        $item2 = $this->createProduct('description2');
        $collection->add($item2);

        $actual = $this->getSortedCollection($collection);
        self::assertSame([$item1, $item2], $actual);
    }

    public function testEmptyCollection(): void
    {
        /** @psalm-var ArrayCollection<array-key, Product> $collection */
        $collection = new ArrayCollection();

        $actual = $this->getSortedCollection($collection);
        self::assertSame([], $actual);

        $actual = $this->getReversedSortedCollection($collection);
        self::assertSame([], $actual);
    }

    public function testReversedSortedCollectionNoPreserveKey(): void
    {
        /** @psalm-var ArrayCollection<array-key, Product> $collection */
        $collection = new ArrayCollection();
        $item1 = $this->createProduct('description1');
        $collection->add($item1);
        $item2 = $this->createProduct('description2');
        $collection->add($item2);

        $actual = $this->getReversedSortedCollection($collection, false);
        self::assertSame([$item2, $item1], $actual);
    }

    public function testReversedSortedCollectionPreserveKey(): void
    {
        /** @psalm-var ArrayCollection<array-key, Product> $collection */
        $collection = new ArrayCollection();
        $item1 = $this->createProduct('description1');
        $collection->add($item1);
        $item2 = $this->createProduct('description2');
        $collection->add($item2);

        $actual = $this->getReversedSortedCollection($collection);
        self::assertSame([1 => $item2, 0 => $item1], $actual);
    }

    private function createProduct(string $description): Product
    {
        $product = new Product();

        return $product->setDescription($description);
    }
}
