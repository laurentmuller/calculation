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
 * Contains parameters to archive calculations.
 */
class ArchiveQuery
{
    private \DateTimeInterface $date;
    private bool $simulate = true;
    /** @var CalculationState[] */
    private array $sources = [];
    private ?CalculationState $target = null;

    public function __construct()
    {
        $date = new \DateTime();
        $interval = new \DateInterval('P6M');
        $this->date = $date->sub($interval);
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    /**
     * @return CalculationState[]
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    public function getTarget(): ?CalculationState
    {
        return $this->target;
    }

    public function isSimulate(): bool
    {
        return $this->simulate;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function setSimulate(bool $simulate): self
    {
        $this->simulate = $simulate;

        return $this;
    }

    /**
     * @param CalculationState[] $sources
     */
    public function setSources(array $sources): self
    {
        $this->sources = $sources;

        return $this;
    }

    public function setTarget(?CalculationState $target): self
    {
        $this->target = $target;

        return $this;
    }
}
