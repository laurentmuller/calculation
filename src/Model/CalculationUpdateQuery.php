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

    /**
     * Returns a value indicating if all calculations must be updated.
     */
    public function isClosed(): bool
    {
        return $this->closed;
    }

    /**
     * Returns a value indicating if the code for groups and categories must be updated.
     */
    public function isCodes(): bool
    {
        return $this->codes;
    }

    /**
     * Returns a value indicating if the duplicated items must be removed.
     */
    public function isDuplicated(): bool
    {
        return $this->duplicated;
    }

    /**
     * Returns a value indicating if the empty items must be removed.
     */
    public function isEmpty(): bool
    {
        return $this->empty;
    }

    /**
     * Returns a value indicating if the update is simulated (no flush changes in the database).
     */
    public function isSimulated(): bool
    {
        return $this->simulated;
    }

    /**
     * Returns a value indicating if the items must be sorted.
     */
    public function isSorted(): bool
    {
        return $this->sorted;
    }

    /**
     * Sets a value indicating if all calculations must be updated.
     */
    public function setClosed(bool $closed): self
    {
        $this->closed = $closed;

        return $this;
    }

    /**
     * Sets a value indicating if the code for groups and categories must be updated.
     */
    public function setCodes(bool $codes): self
    {
        $this->codes = $codes;

        return $this;
    }

    /**
     * Sets a value indicating if the duplicated items must be removed.
     */
    public function setDuplicated(bool $duplicated): self
    {
        $this->duplicated = $duplicated;

        return $this;
    }

    /**
     * Sets a value indicating if the empty items must be removed.
     */
    public function setEmpty(bool $empty): self
    {
        $this->empty = $empty;

        return $this;
    }

    /**
     * Sets a value indicating if the update is simulated (no flush changes in the database).
     */
    public function setSimulated(bool $simulated): self
    {
        $this->simulated = $simulated;

        return $this;
    }

    /**
     * Sets a value indicating if the items must be sorted.
     */
    public function setSorted(bool $sorted): self
    {
        $this->sorted = $sorted;

        return $this;
    }
}
