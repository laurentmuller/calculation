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
use App\Utils\FormatUtils;

/**
 * Contains parameters to archive calculations.
 */
class CalculationArchiveQuery extends AbstractSimulateQuery
{
    private \DateTimeInterface $date;

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

    public function getDateFormatted(): string
    {
        return FormatUtils::formatDate($this->date);
    }

    /**
     * @return CalculationState[]
     */
    public function getSources(): array
    {
        return $this->sources;
    }

    public function getSourcesCode(): string
    {
        $sources = \array_map(static fn (CalculationState $state): string => (string) $state->getCode(), $this->sources);

        return \implode(', ', $sources);
    }

    public function getTarget(): ?CalculationState
    {
        return $this->target;
    }

    public function getTargetCode(): ?string
    {
        return $this->target?->getCode();
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

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
