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

use App\Traits\DuplicateItemsTrait;
use PHPUnit\Framework\TestCase;

class DuplicateItemsTraitTest extends TestCase
{
    use DuplicateItemsTrait;

    public function testEmpty(): void
    {
        $actual = $this->formatItems([]);
        self::assertSame('', $actual);
    }

    public function testPriceCountOne(): void
    {
        $item = [
            'description' => 'description',
            'quantity' => 0.0,
            'price' => 0.0,
            'count' => 1,
        ];

        $actual = $this->formatItems([$item]);
        self::assertSame('description (1)', $actual);
    }

    public function testTwoCount(): void
    {
        $item1 = [
            'description' => 'description1',
            'quantity' => 0.0,
            'price' => 0.0,
            'count' => 10,
        ];
        $item2 = [
            'description' => 'description2',
            'quantity' => 0.0,
            'price' => 0.0,
            'count' => 20,
        ];

        $actual = $this->formatItems([$item1, $item2]);
        self::assertSame("description1 (10)\ndescription2 (20)", $actual);
    }
}
