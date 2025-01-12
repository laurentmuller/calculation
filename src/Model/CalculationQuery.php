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
 * Contains parameters to update the overall total or the user margin of calculations.
 *
 * @psalm-type QueryGroupType = array{id: int, total: float}
 */
readonly class CalculationQuery
{
    /**
     * @param bool                    $adjust     adjust the user margin state
     * @param float                   $userMargin the user margin
     * @param CalculationGroupQuery[] $groups     the groups
     */
    public function __construct(
        public bool $adjust = false,
        public float $userMargin = 0.0,
        public array $groups = []
    ) {
    }
}
