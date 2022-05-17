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
    /**
     * @var array<string, array<Calculation>>
     */
    private array $results = [];
    private bool $simulate = true;
    private ?CalculationState $target = null;

    public function addCalculation(CalculationState $state, Calculation $calculation): self
    {
        $key = (string) $state->getCode();
        $this->results[$key][] = $calculation;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    /**
     * @return array<string, array<Calculation>>
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
        return !empty($this->results);
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
        return \array_reduce($this->results, fn (int $carry, array $calculations): int => $carry + \count($calculations), 0);
    }
}
