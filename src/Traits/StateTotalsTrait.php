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

namespace App\Traits;

use App\Repository\CalculationStateRepository;

/**
 * Trait to get total of calculations by state.
 *
 * @psalm-import-type QueryCalculationType from CalculationStateRepository
 */
trait StateTotalsTrait
{
    use ArrayTrait;

    /**
     * @param QueryCalculationType[] $states
     *
     * @return array{
     *     calculation_count: int,
     *     calculation_percent: float,
     *     items_amount: float,
     *     margin_amount: float,
     *     margin_percent: float,
     *     total_amount: float,
     *     total_percent: float}
     */
    public function getStateTotals(array $states): array
    {
        $count = $this->getColumnSum($states, 'count', 0);
        $count_percent = $this->getColumnSum($states, 'percent_calculation');
        $total_amount = $this->getColumnSum($states, 'total');
        $total_percent = $this->getColumnSum($states, 'percent_amount');
        $items_amount = $this->getColumnSum($states, 'items');
        $margin_percent = $this->safeDivide($total_amount, $items_amount);
        $margin_amount = $total_amount - $items_amount;

        return [
            'calculation_count' => $count,
            'calculation_percent' => $count_percent,
            'items_amount' => $items_amount,
            'margin_amount' => $margin_amount,
            'margin_percent' => $margin_percent,
            'total_amount' => $total_amount,
            'total_percent' => $total_percent,
        ];
    }
}
