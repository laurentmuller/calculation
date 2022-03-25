<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Interfaces;

/**
 * Margin range interface.
 *
 * @author Laurent Muller
 */
interface MarginInterface
{
    /**
     * Returns if the given value is between this minimum (inclusive) and this maximum (exlcusive).
     */
    public function contains(float $value): bool;

    /**
     * Get maximum.
     */
    public function getMaximum(): float;

    /**
     * Get minimum.
     */
    public function getMinimum(): float;
}
