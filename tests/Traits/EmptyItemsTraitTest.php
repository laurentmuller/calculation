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

use App\Traits\EmptyItemsTrait;
use PHPUnit\Framework\TestCase;

class EmptyItemsTraitTest extends TestCase
{
    use EmptyItemsTrait;

    public function testEmpty(): void
    {
        $actual = $this->formatItems([]);
        self::assertSame('', $actual);
    }

    public function testPriceQuantityZero(): void
    {
        $item = [
            'description' => 'description',
            'quantity' => 0.0,
            'price' => 0.0,
            'count' => 1,
        ];

        $actual = $this->formatItems([$item]);
        self::assertSame('description (price, quantity)', $actual);
    }

    public function testPriceZero(): void
    {
        $item = [
            'description' => 'description',
            'quantity' => 1.0,
            'price' => 0.0,
            'count' => 1,
        ];

        $actual = $this->formatItems([$item]);
        self::assertSame('description (price)', $actual);
    }

    public function testQuantityZero(): void
    {
        $item = [
            'description' => 'description',
            'quantity' => 0.0,
            'price' => 1.0,
            'count' => 1,
        ];

        $actual = $this->formatItems([$item]);
        self::assertSame('description (quantity)', $actual);
    }

    public function testTwoPricesZero(): void
    {
        $item1 = [
            'description' => 'description1',
            'quantity' => 1.0,
            'price' => 0.0,
            'count' => 1,
        ];
        $item2 = [
            'description' => 'description2',
            'quantity' => 1.0,
            'price' => 0.0,
            'count' => 1,
        ];

        $actual = $this->formatItems([$item1, $item2]);
        self::assertSame("description1 (price)\ndescription2 (price)", $actual);
    }

    #[\Override]
    protected function getPriceLabel(): string
    {
        return 'price';
    }

    #[\Override]
    protected function getQuantityLabel(): string
    {
        return 'quantity';
    }
}
