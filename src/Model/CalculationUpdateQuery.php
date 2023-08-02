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

use App\Entity\CalculationState;

/**
 * Contains parameters to update overall total of calculations.
 */
class CalculationUpdateQuery extends AbstractSimulateQuery
{
    private \DateTimeInterface $dateFrom;

    private \DateTimeInterface $dateTo;

    /** @var CalculationState[] */
    private array $states = [];

    public function __construct()
    {
        $date = new \DateTime();
        $date = $date->setTime(0, 0);
        $this->dateFrom = $date->sub(new \DateInterval('P1M'));
        $this->dateTo = new \DateTime();
    }

    public function getDateFrom(): \DateTimeInterface
    {
        return $this->dateFrom;
    }

    public function getDateTo(): \DateTimeInterface
    {
        return $this->dateTo;
    }

    /**
     * @return CalculationState[]
     */
    public function getStates(): array
    {
        return $this->states;
    }

    public function getStatesCode(): string
    {
        $sources = \array_map(static fn (CalculationState $state): string => (string) $state->getCode(), $this->states);

        return \implode(', ', $sources);
    }

    public function setDateFrom(\DateTimeInterface $dateFrom): self
    {
        $this->dateFrom = $dateFrom;

        return $this;
    }

    public function setDateTo(\DateTimeInterface $dateTo): self
    {
        $this->dateTo = $dateTo;

        return $this;
    }

    /**
     * @param CalculationState[] $states
     */
    public function setStates(array $states): self
    {
        $this->states = $states;

        return $this;
    }
}
