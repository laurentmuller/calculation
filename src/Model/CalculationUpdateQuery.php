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
    private bool $closeCalculations = false;
    private bool $copyCodes = true;
    private bool $duplicateItems = false;
    private bool $emptyCalculations = true;
    private bool $emptyItems = true;
    private bool $simulate = true;
    private bool $sortItems = true;

    /**
     * Returns a value indicating if all calculations must be updated.
     */
    public function isCloseCalculations(): bool
    {
        return $this->closeCalculations;
    }

    /**
     * Returns a value indicating if the code for groups and categories must be updated.
     */
    public function isCopyCodes(): bool
    {
        return $this->copyCodes;
    }

    /**
     * Returns a value indicating if the duplicated items must be removed.
     */
    public function isDuplicateItems(): bool
    {
        return $this->duplicateItems;
    }

    /**
     * Returns a value indicating if the empty calculations must be removed.
     */
    public function isEmptyCalculations(): bool
    {
        return $this->emptyCalculations;
    }

    /**
     * Returns a value indicating if the empty items must be removed.
     */
    public function isEmptyItems(): bool
    {
        return $this->emptyItems;
    }

    /**
     * Returns a value indicating if the update is simulated (no flush changes in the database).
     */
    public function isSimulate(): bool
    {
        return $this->simulate;
    }

    /**
     * Returns a value indicating if the items must be sorted.
     */
    public function isSortItems(): bool
    {
        return $this->sortItems;
    }

    /**
     * Sets a value indicating if all calculations must be updated.
     */
    public function setCloseCalculations(bool $closeCalculations): self
    {
        $this->closeCalculations = $closeCalculations;

        return $this;
    }

    /**
     * Sets a value indicating if the code for groups and categories must be updated.
     */
    public function setCopyCodes(bool $copyCodes): self
    {
        $this->copyCodes = $copyCodes;

        return $this;
    }

    /**
     * Sets a value indicating if the duplicated items must be removed.
     */
    public function setDuplicateItems(bool $duplicateItems): self
    {
        $this->duplicateItems = $duplicateItems;

        return $this;
    }

    /**
     * Sets a value indicating if the empty calculations must be removed.
     */
    public function setEmptyCalculations(bool $emptyCalculations): self
    {
        $this->emptyCalculations = $emptyCalculations;

        return $this;
    }

    /**
     * Sets a value indicating if the empty items must be removed.
     */
    public function setEmptyItems(bool $emptyItems): self
    {
        $this->emptyItems = $emptyItems;

        return $this;
    }

    /**
     * Sets a value indicating if the update is simulated (no flush changes in the database).
     */
    public function setSimulate(bool $simulate): self
    {
        $this->simulate = $simulate;

        return $this;
    }

    /**
     * Sets a value indicating if the items must be sorted.
     */
    public function setSortItems(bool $sortItems): self
    {
        $this->sortItems = $sortItems;

        return $this;
    }
}
