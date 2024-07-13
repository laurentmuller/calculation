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
}
