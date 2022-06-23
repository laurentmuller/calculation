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
 */
class ArchiveResult
{
    private ?\DateTimeInterface $date = null;
    /** @var array<string, array{state: CalculationState, calculations: array<Calculation>}> */
    private array $results = [];
    private bool $simulate = true;
    private ?CalculationState $target = null;
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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    /**
     * @return array<string, array{state: CalculationState, calculations: array<Calculation>}>
     */
    public function getResults(): array
    {
        \ksort($this->results, \SORT_LOCALE_STRING);

        return $this->results;
    }

    public function getTarget(): ?CalculationState
    {
        return $this->target;
    }

    public function isSimulate(): bool
    {
        return $this->simulate;
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

    public function setDate(?\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function setSimulate(bool $simulate): self
    {
        $this->simulate = $simulate;

        return $this;
    }

    public function setTarget(?CalculationState $target): self
    {
        $this->target = $target;

        return $this;
    }

    public function total(): int
    {
        return $this->total;
    }
}
