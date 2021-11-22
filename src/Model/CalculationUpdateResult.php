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
    private int $copyCodes = 0;
    private array $descriptions = [];
    private int $duplicateItems = 0;
    private int $emptyCalculations = 0;
    private int $emptyItems = 0;
    private bool $simulate = true;
    private int $skipCalculations = 0;
    private int $sortItems = 0;
    private int $totalCalculations = 0;
    private int $unmodifiableCalculations = 0;

    /**
     * Add a calculation to the list of updated calculations.
     *
     * @param string[]|string $messages
     */
    public function addCalculation(Calculation $calculation, $messages, bool $deleted = false): self
    {
        $this->calculations[] = [
            'id' => $calculation->getId(),
            'date' => $calculation->getDate(),
            'customer' => $calculation->getCustomer(),
            'description' => $calculation->getDescription(),
            'overallMargin' => $calculation->getOverallMargin(),
            'overallTotal' => $calculation->getOverallTotal(),
            'stateCode' => $calculation->getStateCode(),
            'stateColor' => $calculation->getStateColor(),
            'messages' => (array) $messages,
            'deleted' => $deleted,
        ];

        return $this;
    }

    /**
     * Adds the number of updated groups or categories codes.
     */
    public function addCopyCodes(int $value): self
    {
        $this->copyCodes += $value;

        return $this;
    }

    /**
     * Adds the number of duplicate items.
     */
    public function addDuplicatedItems(int $value): self
    {
        $this->duplicateItems += $value;

        return $this;
    }

    /**
     * Adds the number of empty calculations.
     */
    public function addEmptyCalculations(int $value): self
    {
        $this->emptyCalculations += $value;

        return $this;
    }

    /**
     * Adds the number of empty items.
     */
    public function addEmptyItems(int $value): self
    {
        $this->emptyItems += $value;

        return $this;
    }

    /**
     * Adds the number of skipped calculations.
     */
    public function addSkipCalculations(int $value): self
    {
        $this->skipCalculations += $value;

        return $this;
    }

    /**
     * Adds the number of sorted calculations.
     */
    public function addSortItems(int $value): self
    {
        $this->sortItems += $value;

        return $this;
    }

    /**
     * Adds the number of updated calculations.
     */
    public function addTotalCalculations(int $value): self
    {
        $this->totalCalculations += $value;

        return $this;
    }

    /**
     * Adds the number of unmodifiable calculations.
     */
    public function addUnmodifiableCalculations(int $value): self
    {
        $this->unmodifiableCalculations += $value;

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
     * Gets the number of updated groups and categories codes.
     */
    public function getCopyCodes(): int
    {
        return $this->copyCodes;
    }

    /**
     * Gets the update descriptions.
     *
     * @return array<int, string[]>
     */
    public function getDescriptions(): array
    {
        return $this->descriptions;
    }

    /**
     * Gets the number of duplicate items.
     */
    public function getDuplicateItems(): int
    {
        return $this->duplicateItems;
    }

    /**
     * Gets the number of empty calculations.
     */
    public function getEmptyCalculations(): int
    {
        return $this->emptyCalculations;
    }

    /**
     * Gets the number of empty items.
     */
    public function getEmptyItems(): int
    {
        return $this->emptyItems;
    }

    /**
     * Getd the number of skipped calculations.
     */
    public function getSkipCalculations(): int
    {
        return $this->skipCalculations;
    }

    /**
     * Gets the number of sorted calculations.
     */
    public function getSortItems(): int
    {
        return $this->sortItems;
    }

    /**
     * Gets the number of calculations (updated and skipped).
     */
    public function getTotalCalculations(): int
    {
        return $this->totalCalculations;
    }

    /**
     * Gets the number of unmodifiable calculations.
     */
    public function getUnmodifiableCalculations(): int
    {
        return $this->unmodifiableCalculations;
    }

    /**
     * Gets the number of updated calculations.
     */
    public function getUpdateCalculations(): int
    {
        return \count($this->calculations);
    }

    /**
     * Returns a value indicating if the update is simulated (no flush changes in the database).
     */
    public function isSimulate(): bool
    {
        return $this->simulate;
    }

    /**
     * Returns if the update is valid.
     */
    public function isValid(): bool
    {
        return !empty($this->calculations);
    }

    /**
     * Sets a value indicating if the update is simulated (no flush changes in the database).
     */
    public function setSimulate(bool $simulate): self
    {
        $this->simulate = $simulate;

        return $this;
    }
}
