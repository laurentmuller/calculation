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

/**
 * Contains result of updated states, cities and streets.
 *
 * @author Laurent Muller
 */
class SwissPostUpdateResult
{
    private ?string $error = null;
    private int $errorCities = 0;
    private int $errorStates = 0;
    private int $errorStreets = 0;
    private int $validCities = 0;
    private ?\DateTimeInterface $validity = null;
    private int $validStates = 0;
    private int $validStreets = 0;

    /**
     * Adds the number of error cities.
     */
    public function addErrorCities(int $value = 1): self
    {
        $this->errorCities += $value;

        return $this;
    }

    /**
     * Adds the number of error states.
     */
    public function addErrorStates(int $value = 1): self
    {
        $this->errorStates += $value;

        return $this;
    }

    /**
     * Adds the number of error streets.
     */
    public function addErrorStreets(int $value = 1): self
    {
        $this->errorStreets += $value;

        return $this;
    }

    /**
     * Adds the number of valid cities.
     */
    public function addValidCities(int $value = 1): self
    {
        $this->validCities += $value;

        return $this;
    }

    /**
     * Adds the number of valid states.
     */
    public function addValidStates(int $value = 1): self
    {
        $this->validStates += $value;

        return $this;
    }

    /**
     * Adds the number of valid streets.
     */
    public function addValidStreets(int $value = 1): self
    {
        $this->validStreets += $value;

        return $this;
    }

    /**
     * Gets the error.
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Gets the number of error cities.
     */
    public function getErrorCities(): int
    {
        return $this->errorCities;
    }

    /**
     * Gets the total number of errors.
     */
    public function getErrors(): int
    {
        return $this->errorStates + $this->errorCities + $this->errorStreets;
    }

    /**
     * Gets the  number of error states.
     */
    public function getErrorStates(): int
    {
        return $this->errorStates;
    }

    /**
     * Gets the  number of  error streets.
     */
    public function getErrorStreets(): int
    {
        return $this->errorStreets;
    }

    /**
     * Gets the  number of valid cities.
     */
    public function getValidCities(): int
    {
        return $this->validCities;
    }

    /**
     * Gets the validity date.
     */
    public function getValidity(): ?\DateTimeInterface
    {
        return $this->validity;
    }

    /**
     * Gets the total number of valid entries.
     */
    public function getValids(): int
    {
        return $this->validStates + $this->validCities + $this->validStreets;
    }

    /**
     * Gets the  number of valid states.
     */
    public function getValidStates(): int
    {
        return $this->validStates;
    }

    /**
     * Gets the  number of valid streets.
     */
    public function getValidStreets(): int
    {
        return $this->validStreets;
    }

    /**
     * Returns if the update is valid.
     */
    public function isValid(): bool
    {
        return empty($this->error);
    }

    /**
     * Sets the error message.
     */
    public function setError(?string $error): self
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Sets the validity date.
     */
    public function setValidity(\DateTimeInterface $validity): self
    {
        $this->validity = $validity;

        return $this;
    }
}
