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
 * Contains a computed calculation group.
 */
class GroupType
{
    public function __construct(
        public int $id,
        public string $description,
        public float $marginPercent,
        public float $marginAmount,
        public float $amount,
        public float $total
    ) {
    }
}
