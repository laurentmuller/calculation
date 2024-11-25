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

use App\Traits\StateTotalsTrait;
use PHPUnit\Framework\TestCase;

class StateTotalsTraitTest extends TestCase
{
    use StateTotalsTrait;

    public function testStateTotals(): void
    {
        $type = [
            'id' => 1,
            'code' => 'code',
            'editable' => true,
            'color' => 'color',
            'count' => 1,
            'items' => 1.0,
            'total' => 1.0,
            'margin_percent' => 1.0,
            'margin_amount' => 1.0,
            'percent_calculation' => 1.0,
            'percent_amount' => 1.0,
        ];
        $actual = $this->getStateTotals(['key' => $type]);
        self::assertSame(1, $actual['calculation_count']);
        self::assertSame(1.0, $actual['calculation_percent']);
        self::assertSame(1.0, $actual['items_amount']);
        self::assertSame(0.0, $actual['margin_amount']);
        self::assertSame(1.0, $actual['margin_percent']);
        self::assertSame(1.0, $actual['total_amount']);
        self::assertSame(1.0, $actual['total_percent']);
    }
}
