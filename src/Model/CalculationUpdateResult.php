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

/**
 * Contains result of updated calculations.
 *
 * @author Laurent Muller
 */
class CalculationUpdateResult
{
    private int $codes = 0;
    private int $duplicated = 0;
    private int $empty = 0;
    private int $skipped = 0;
    private int $sorted = 0;
    private int $total = 0;
    private int $unmodifiable = 0;
    private int $updated = 0;

    public function addCodes(int $value): self
    {
        $this->codes += $value;

        return $this;
    }

    public function addDuplicated(int $value): self
    {
        $this->duplicated += $value;

        return $this;
    }

    public function addEmpty(int $value): self
    {
        $this->empty += $value;

        return $this;
    }

    public function addSkipped(int $value): self
    {
        $this->skipped += $value;

        return $this;
    }

    public function addSorted(int $value): self
    {
        $this->sorted += $value;

        return $this;
    }

    public function addTotal(int $value): self
    {
        $this->total += $value;

        return $this;
    }

    public function addUnmodifiable(int $value): self
    {
        $this->unmodifiable += $value;

        return $this;
    }

    public function addUpdated(int $value): self
    {
        $this->updated += $value;

        return $this;
    }

    public function getCodes(): int
    {
        return $this->codes;
    }

    public function getDuplicated(): int
    {
        return $this->duplicated;
    }

    public function getEmpty(): int
    {
        return $this->empty;
    }

    public function getSkipped(): int
    {
        return $this->skipped;
    }

    public function getSorted(): int
    {
        return $this->sorted;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getUnmodifiable(): int
    {
        return $this->unmodifiable;
    }

    public function getUpdated(): int
    {
        return $this->updated;
    }

    public function isValid(): bool
    {
        return $this->updated > 0;
    }
}
