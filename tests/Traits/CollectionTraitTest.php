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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CollectionTrait::class)]
class CollectionTraitTest extends TestCase
{
    use CollectionTrait;

    public function testComparableSorted(): void
    {
        /** @psalm-var ArrayCollection<array-key, Product> $collection */
        $collection = new ArrayCollection();
        $actual = $this->getSortedCollection($collection);
        self::assertSame([], $actual);

        $item1 = new Product();
        $item1->setDescription('description1');
        $collection->add($item1);

        $item2 = new Product();
        $item2->setDescription('description2');
        $collection->add($item2);

        $actual = $this->getSortedCollection($collection);
        self::assertSame([$item1, $item2], $actual);
    }

    public function testFindFirst(): void
    {
        $collection = new ArrayCollection();
        $closure = fn (int $key, int $value): bool => 2 === $value;
        $actual = $this->findFirst($collection, $closure);
        self::assertNull($actual);

        $collection = new ArrayCollection([1, 2, 3]);
        $closure = fn (int $key, int $value): bool => 2 === $value;
        $actual = $this->findFirst($collection, $closure);
        self::assertSame(2, $actual);

        $closure = fn (int $key, int $value): bool => 4 === $value;
        $actual = $this->findFirst($collection, $closure);
        self::assertNull($actual);
    }
}
