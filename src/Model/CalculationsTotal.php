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

namespace App\Model;

use App\Traits\MathTrait;

class CalculationsTotal
{
    use MathTrait;

    public readonly float $marginAmount;
    public readonly float $marginPercent;

    public function __construct(
        public readonly int $count,
        public readonly float $items,
        public readonly float $total
    ) {
        $this->marginAmount = $this->round($this->total - $this->items, 2);
        $this->marginPercent = $this->round($this->safeDivide($this->total, $this->items), 4);
    }
}
