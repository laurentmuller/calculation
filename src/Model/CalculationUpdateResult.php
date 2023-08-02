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

use App\Entity\Calculation;

/**
 * Contains result of update overall total of calculations.
 *
 * @psalm-type ResultType = array{calculation: Calculation, oldTotal: float, delta: float}
 */
class CalculationUpdateResult implements \Countable
{
    /**
     * @var ResultType[]
     */
    private array $results = [];

    /**
     *  Adds the given calculation to the results.
     *
     * @param float       $oldTotal    the old overall total
     * @param Calculation $calculation the updated calculation
     */
    public function addCalculation(float $oldTotal, Calculation $calculation): self
    {
        $this->results[] = [
            'oldTotal' => $oldTotal,
            'calculation' => $calculation,
            'delta' => $oldTotal - $calculation->getOverallTotal(),
        ];

        return $this;
    }

    public function count(): int
    {
        return \count($this->results);
    }

    /**
     * @return ResultType[]
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Returns if the update is valid.
     */
    public function isValid(): bool
    {
        return [] !== $this->results;
    }
}
