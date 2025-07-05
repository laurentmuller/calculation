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

use Symfony\Component\Clock\DatePoint;

/**
 * Contains the result of updated states, cities and streets.
 *
 * @phpstan-type SwissPostResultType = array{state: int, city: int, street: int}
 */
class SwissPostUpdateResult
{
    private ?string $error = null;
    /** @phpstan-var SwissPostResultType */
    private array $invalidEntries = ['state' => 0, 'city' => 0, 'street' => 0];
    private int $invalidEntriesCount = 0;
    /** @phpstan-var SwissPostResultType */
    private array $oldEntries = ['state' => 0, 'city' => 0, 'street' => 0];
    private bool $overwrite = false;
    private string $sourceFile = '';
    private string $sourceName = '';
    /** @phpstan-var SwissPostResultType */
    private array $validEntries = ['state' => 0, 'city' => 0, 'street' => 0];
    private int $validEntriesCount = 0;
    private ?DatePoint $validity = null;

    /**
     * Adds a parsed city to the results.
     */
    public function addCity(bool $valid): self
    {
        return $this->add($valid, 'city');
    }

    /**
     * Adds a parsed state to the results.
     */
    public function addState(bool $valid): self
    {
        return $this->add($valid, 'state');
    }

    /**
     * Adds a parsed street to the results.
     */
    public function addStreet(bool $valid): self
    {
        return $this->add($valid, 'street');
    }

    /**
     * Gets the error message.
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Gets the error entries.
     *
     * @phpstan-return SwissPostResultType
     */
    public function getInvalidEntries(): array
    {
        return $this->invalidEntries;
    }

    /**
     * Gets the total number of invalid entries.
     */
    public function getInvalidEntriesCount(): int
    {
        return $this->invalidEntriesCount;
    }

    /**
     * Gets the existing tables count.
     *
     * @phpstan-return SwissPostResultType
     */
    public function getOldEntries(): array
    {
        return $this->oldEntries;
    }

    /**
     * Gets the total number of old entries.
     */
    public function getOldEntriesCount(): int
    {
        return \array_sum($this->oldEntries);
    }

    /**
     * Gets the source (archive) file.
     */
    public function getSourceFile(): string
    {
        return $this->sourceFile;
    }

    /**
     * Gets the source (archive name) file name.
     */
    public function getSourceName(): string
    {
        return $this->sourceName;
    }

    /**
     * Gets the valid entries.
     *
     * @phpstan-return SwissPostResultType
     */
    public function getValidEntries(): array
    {
        return $this->validEntries;
    }

    /**
     * Gets the total number of valid entries.
     */
    public function getValidEntriesCount(): int
    {
        return $this->validEntriesCount;
    }

    /**
     * Gets the validity date.
     */
    public function getValidity(): ?DatePoint
    {
        return $this->validity;
    }

    /**
     * Gets the overwritten option.
     */
    public function isOverwrite(): bool
    {
        return $this->overwrite;
    }

    /**
     * Returns if the update is valid.
     */
    public function isValid(): bool
    {
        return null === $this->error;
    }

    /**
     * Sets the error message.
     */
    public function setError(string $error): self
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Sets the existing tables count.
     *
     * @phpstan-param SwissPostResultType $oldEntries
     */
    public function setOldEntries(array $oldEntries): self
    {
        $this->oldEntries = $oldEntries;

        return $this;
    }

    /**
     * Sets the overwritten option.
     */
    public function setOverwrite(bool $overwrite): self
    {
        $this->overwrite = $overwrite;

        return $this;
    }

    /**
     * Sets the source (archive) file.
     */
    public function setSourceFile(string $sourceFile): self
    {
        $this->sourceFile = $sourceFile;

        return $this;
    }

    /**
     * Sets the source (archive name) file name.
     */
    public function setSourceName(string $sourceName): self
    {
        $this->sourceName = $sourceName;

        return $this;
    }

    /**
     * Sets the validity date.
     */
    public function setValidity(DatePoint $validity): self
    {
        $this->validity = $validity;

        return $this;
    }

    /**
     * @phpstan-param 'state'|'city'|'street' $key
     */
    private function add(bool $valid, string $key): self
    {
        if ($valid) {
            ++$this->validEntriesCount;
            ++$this->validEntries[$key];
        } else {
            ++$this->invalidEntriesCount;
            ++$this->invalidEntries[$key];
        }

        return $this;
    }
}
