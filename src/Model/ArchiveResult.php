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

class ArchiveResult
{
    /**
     * @var array<string, array<int, Calculation>>
     */
    private array $results = [];

    public function __construct(private readonly \DateTimeInterface $date, private readonly ?CalculationState $target, private readonly bool $simulate)
    {
    }

    public function addCalculation(CalculationState $state, Calculation $calculation): self
    {
        $key = (string) $state->getCode();
        $this->results[$key][] = $calculation;

        return $this;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    /**
     * @return array<string, array<int, Calculation>>
     */
    public function getResults(): array
    {
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

    public function total(): int
    {
        return \array_reduce($this->results, fn (int $carry, array $calculations): int => $carry + \count($calculations), 0);
    }
}
