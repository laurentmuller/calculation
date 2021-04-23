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

namespace App\Calendar;

use App\Entity\Calculation;

/**
 * Trait to manage an array of calculations.
 *
 * @author Laurent Muller
 */
trait CalculationsTrait
{
    /**
     * @var Calculation[]
     */
    protected array $calculations = [];

    /**
     * Add a calculation.
     *
     * @param Calculation $calculation the calculation to add
     */
    public function addCalculation(Calculation $calculation): self
    {
        $this->calculations[(int) $calculation->getId()] = $calculation;

        return $this;
    }

    /**
     * Returns the number of calculations.
     */
    public function count(): int
    {
        return \count($this->calculations);
    }

    /**
     * Gets the calculations.
     *
     * @return Calculation[]
     */
    public function getCalculations(): array
    {
        return $this->calculations;
    }

    /**
     * Returns a value indicating if this is empty (contains no calculation).
     */
    public function isEmpty(): bool
    {
        return empty($this->calculations);
    }
}
