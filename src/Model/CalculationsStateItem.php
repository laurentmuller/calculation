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

class CalculationsStateItem extends CalculationsTotal
{
    public float $calculationsPercent = 0.0;
    public float $totalPercent = 0.0;

    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly bool $editable,
        public readonly string $color,
        int $count,
        float $items,
        float $total,
    ) {
        parent::__construct($count, $items, $total);
    }
}
