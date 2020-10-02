<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
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
    protected $calculations = [];

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
