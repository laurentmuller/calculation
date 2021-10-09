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

use App\Entity\Calculation;

/**
 * Class implementing this interface deals with a parent calculation.
 *
 * @author Laurent Muller
 */
interface ParentCalculationInterface
{
    /**
     * Gets the parent's calculation.
     */
    public function getCalculation(): ?Calculation;
}
