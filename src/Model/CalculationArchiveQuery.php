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
use App\Utils\DateUtils;
use App\Utils\FormatUtils;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Contains parameters to archive calculations.
 */
class CalculationArchiveQuery extends AbstractSimulateQuery
{
    private \DateTimeInterface $date;

    /** @var CalculationState[] */
    #[Assert\Count(min: 1)]
    private array $sources = [];

    private ?CalculationState $target = null;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->date = DateUtils::sub(DateUtils::removeTime(), 'P6M');
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

    /**
     * @return int[]
     */
    public function getSourcesId(): array
    {
        return \array_map(static fn (CalculationState $state): int => (int) $state->getId(), $this->sources);
    }

    public function getTarget(): ?CalculationState
    {
        return $this->target;
    }

    public function getTargetCode(): ?string
    {
        return $this->target?->getCode();
    }

    public function getTargetId(): ?int
    {
        return $this->target?->getId();
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
