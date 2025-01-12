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
 * Contains a single query group values.
 */
readonly class CalculationGroupQuery
{
    /**
     * @param int   $id    the group identifier
     * @param float $total the group's total
     */
    public function __construct(
        public int $id = 0,
        public float $total = 0.0
    ) {
    }
}
