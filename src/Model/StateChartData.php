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

/**
 * @extends ChartData<StateChartDataItem>
 */
readonly class StateChartData extends ChartData
{
    #[\Override]
    protected function generateTotalItem(): ChartDataItem
    {
        $total = parent::generateTotalItem();
        foreach ($this->items as $item) {
            $item->updatePercents($total);
        }

        return $total;
    }
}
