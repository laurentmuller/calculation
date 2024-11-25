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
use App\Traits\ComparableSortTrait;
use PHPUnit\Framework\TestCase;

class ComparableSortTraitTest extends TestCase
{
    use ComparableSortTrait;

    public function testComparableSorted(): void
    {
        $values = [];
        $actual = $this->getSortedComparable($values);
        self::assertSame([], $actual);

        $item1 = new Product();
        $item1->setDescription('description1');
        $item2 = new Product();
        $item2->setDescription('description2');
        $values[] = $item1;
        $values[] = $item2;

        $actual = $this->getSortedComparable($values);
        self::assertSame([$item1, $item2], $actual);
    }
}
