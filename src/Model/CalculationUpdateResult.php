<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Model;

use App\Entity\Calculation;

/**
 * Contains result of updated calculations.
 *
 * @author Laurent Muller
 */
class CalculationUpdateResult
{
    private array $calculations = [];
    private int $codes = 0;
    private int $duplicated = 0;
    private int $empty = 0;
    private int $skipped = 0;
    private int $sorted = 0;
    private int $total = 0;
    private int $unmodifiable = 0;

    /**
     * Add a calculation to the list of updated calculations.
     */
    public function addCalculation(Calculation $calculation): self
    {
        $this->calculations[$calculation->getId()] = $calculation;

        return $this;
    }

    /**
     * Adds the number of updated groups or categories codes.
     */
    public function addCodes(int $value): self
    {
        $this->codes += $value;

        return $this;
    }

    /**
     * Adds the number of duplicate items.
     */
    public function addDuplicated(int $value): self
    {
        $this->duplicated += $value;

        return $this;
    }

    /**
     * Adds the number of empty items.
     */
    public function addEmpty(int $value): self
    {
        $this->empty += $value;

        return $this;
    }

    /**
     * Adds the number of skipped calculations.
     */
    public function addSkipped(int $value): self
    {
        $this->skipped += $value;

        return $this;
    }

    /**
     * Adds the number of sorted calculations.
     */
    public function addSorted(int $value): self
    {
        $this->sorted += $value;

        return $this;
    }

    /**
     * Adds the number of updated calculations.
     */
    public function addTotal(int $value): self
    {
        $this->total += $value;

        return $this;
    }

    /**
     * Adds the number of unmodifiable calculations.
     */
    public function addUnmodifiable(int $value): self
    {
        $this->unmodifiable += $value;

        return $this;
    }

    /**
     * Gets the updated calculations.
     */
    public function getCalculations(): array
    {
        return $this->calculations;
    }

    /**
     * Gets the number of updated groups or categories codes.
     */
    public function getCodes(): int
    {
        return $this->codes;
    }

    /**
     * Gets the number of duplicate items.
     */
    public function getDuplicated(): int
    {
        return $this->duplicated;
    }

    /**
     * Gets the number of empty items.
     */
    public function getEmpty(): int
    {
        return $this->empty;
    }

    /**
     * Getd the number of skipped calculations.
     */
    public function getSkipped(): int
    {
        return $this->skipped;
    }

    /**
     * Gets the number of sorted calculations.
     */
    public function getSorted(): int
    {
        return $this->sorted;
    }

    /**
     * Gets the number of calculations (updated and skipped).
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * Gets the number of unmodifiable calculations.
     */
    public function getUnmodifiable(): int
    {
        return $this->unmodifiable;
    }

    /**
     * Gets the number of updated calculations.
     */
    public function getUpdated(): int
    {
        return \count($this->calculations);
    }

    /**
     * Returns if the update is valid.
     */
    public function isValid(): bool
    {
        return !empty($this->calculations);
    }
}
