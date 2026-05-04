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

/**
 * @extends ChartData<StateChartDataItem>
 */
readonly class StateChartData extends ChartData
{
    use MathTrait;

    #[\Override]
    protected function generateTotalItem(): ChartDataItem
    {
        $total = parent::generateTotalItem();
        foreach ($this->items as $item) {
            $item->calculationsPercent = $this->round($this->safeDivide($item->count, $total->count), 4);
            $item->totalPercent = $this->round($this->safeDivide($item->total, $total->total), 4);
        }

        return $total;
    }
}
