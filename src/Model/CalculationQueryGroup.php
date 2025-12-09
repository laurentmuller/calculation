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
 * Contains the group identifier and the total.
 */
class CalculationQueryGroup
{
    public function __construct(public int $id, public float $total)
    {
    }
}
