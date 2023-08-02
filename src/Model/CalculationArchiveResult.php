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
use App\Entity\CalculationState;

/**
 * Contains result of archive calculations.
 *
 * @psalm-type ResultsType = array<string, array{state: CalculationState, calculations: array<Calculation>}>
 */
class CalculationArchiveResult
{
    /** @psalm-var ResultsType */
    private array $results = [];

    private int $total = 0;

    /**
     * Adds the given calculation to the results.
     *
     * @param CalculationState $state       the old calculation state
     * @param Calculation      $calculation the updated calculation
     */
    public function addCalculation(CalculationState $state, Calculation $calculation): self
    {
        $key = (string) $state->getCode();
        $this->results[$key]['state'] = $state;
        $this->results[$key]['calculations'][] = $calculation;
        ++$this->total;

        return $this;
    }

    /**
     * @psalm-return ResultsType
     */
    public function getResults(): array
    {
        \ksort($this->results, \SORT_LOCALE_STRING);

        return $this->results;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function isValid(): bool
    {
        return $this->total > 0;
    }

    public function reset(): self
    {
        $this->results = [];
        $this->total = 0;

        return $this;
    }
}
