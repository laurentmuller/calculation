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
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

final class CollectionTraitTest extends TestCase
{
    use CollectionTrait;

    public function testEmpty(): void
    {
        $collection = $this->createCollection();

        $actual = $this->getSortedCollection($collection);
        self::assertSame([], $actual);

        $actual = $this->getSortedCollection($collection, true);
        self::assertSame([], $actual);
    }

    public function testOneValue(): void
    {
        $product = $this->createProduct('description1');

        $collection = $this->createCollection($product);
        $actual = $this->getSortedCollection($collection);
        self::assertSame([0 => $product], $actual);

        $collection = $this->createCollection($product);
        $actual = $this->getSortedCollection($collection, true);
        self::assertSame([0 => $product], $actual);
    }

    public function testSorted(): void
    {
        $product1 = $this->createProduct('description1');
        $product2 = $this->createProduct('description2');
        $collection = $this->createCollection($product1, $product2);
        $actual = $this->getSortedCollection($collection);
        self::assertSame([0 => $product1, 1 => $product2], $actual);
    }

    public function testSortedReverse(): void
    {
        $product1 = $this->createProduct('description1');
        $product2 = $this->createProduct('description2');
        $collection = $this->createCollection($product1, $product2);
        $actual = $this->getSortedCollection($collection, true);
        self::assertSame([1 => $product2, 0 => $product1], $actual);
    }

    /**
     * @return Collection<int, Product>
     */
    private function createCollection(Product ...$products): Collection
    {
        $collection = new ArrayCollection();
        foreach ($products as $product) {
            $collection->add($product);
        }

        return $collection;
    }

    private function createProduct(string $description): Product
    {
        $product = new Product();
        $product->setDescription($description);

        return $product;
    }
}
