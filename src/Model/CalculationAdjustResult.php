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

use Doctrine\Common\Collections\Collection;

/**
 * Contains a calculation adjust result.
 */
class CalculationAdjustResult
{
    public string $view = '';

    /**
     * @param Collection<int, CalculationResultGroup> $groups
     */
    public function __construct(
        public readonly bool $overallBelow,
        public readonly float $overallMargin,
        public readonly float $overallTotal,
        public readonly float $userMargin,
        public readonly float $minMargin,
        public readonly Collection $groups,
        public readonly bool $adjust = false,
        public readonly bool $result = true
    ) {
    }

    public function toArray(): array
    {
        return (array) $this;
    }
}
