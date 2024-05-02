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

use App\Entity\Calculation;

/**
 * Contains the result of an update overall total of calculations.
 */
class CalculationUpdateResult implements \Countable
{
    private array $results = [];

    /**
     *  Adds the given calculation to the results.
     *
     * @param float       $oldTotal    the old overall total
     * @param Calculation $calculation the updated calculation
     */
    public function addCalculation(float $oldTotal, Calculation $calculation): self
    {
        $this->results[] = [
            'id' => $calculation->getId(),
            'color' => $calculation->getStateColor(),
            'date' => $calculation->getDate(),
            'customer' => $calculation->getCustomer(),
            'description' => $calculation->getDescription(),
            'old_value' => $oldTotal,
            'new_value' => $calculation->getOverallTotal(),
            'delta' => $oldTotal - $calculation->getOverallTotal(),
        ];

        return $this;
    }

    public function count(): int
    {
        return \count($this->results);
    }

    /**
     * @psalm-api
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Returns if the update is valid.
     */
    public function isValid(): bool
    {
        return [] !== $this->results;
    }
}
