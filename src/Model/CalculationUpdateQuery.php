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
 * Contains query parameters to update calculations.
 *
 * @author Laurent Muller
 */
class CalculationUpdateQuery
{
    private bool $closed = false;
    private bool $codes = true;
    private bool $duplicated = false;
    private bool $empty = true;
    private bool $simulated = true;
    private bool $sorted = true;

    public function isClosed(): bool
    {
        return $this->closed;
    }

    public function isCodes(): bool
    {
        return $this->codes;
    }

    public function isDuplicated(): bool
    {
        return $this->duplicated;
    }

    public function isEmpty(): bool
    {
        return $this->empty;
    }

    public function isSimulated(): bool
    {
        return $this->simulated;
    }

    public function isSorted(): bool
    {
        return $this->sorted;
    }

    public function setClosed(bool $closed): self
    {
        $this->closed = $closed;

        return $this;
    }

    public function setCodes(bool $codes): self
    {
        $this->codes = $codes;

        return $this;
    }

    public function setDuplicated(bool $duplicated): self
    {
        $this->duplicated = $duplicated;

        return $this;
    }

    public function setEmpty(bool $empty): self
    {
        $this->empty = $empty;

        return $this;
    }

    public function setSimulated(bool $simulated): self
    {
        $this->simulated = $simulated;

        return $this;
    }

    public function setSorted(bool $sorted): self
    {
        $this->sorted = $sorted;

        return $this;
    }
}
