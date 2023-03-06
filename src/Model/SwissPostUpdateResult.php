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
 * @psalm-type SwissPostResultType = array{state: int, city: int, street: int}
 */
class SwissPostUpdateResult
{
    private ?string $error = null;
    /** @var SwissPostResultType */
    private array $errors = [
            'state' => 0,
            'city' => 0,
            'street' => 0,
        ];
    private bool $overwrite = false;
    private string $sourceFile = '';
    private string $sourceName = '';
    private ?\DateTimeInterface $validity = null;
    /** @var SwissPostResultType */
    private array $valids = [
        'state' => 0,
        'city' => 0,
        'street' => 0,
    ];

    /**
     * Adds a parsed city to the results.
     *
     * @param bool $valid true if valid; false if not
     *
     * @return bool the valid argument
     */
    public function addCity(bool $valid): bool
    {
        return $this->add($valid, 'city');
    }

    /**
     * Adds a parsed state to the results.
     *
     * @param bool $valid true if valid; false if not
     *
     * @return bool the valid argument
     */
    public function addState(bool $valid): bool
    {
        return $this->add($valid, 'state');
    }

    /**
     * Adds a parsed street to the results.
     *
     * @param bool $valid true if valid; false if not
     *
     * @return bool the valid argument
     */
    public function addStreet(bool $valid): bool
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
     * Gets the total number of errors.
     */
    public function getErrorCount(): int
    {
        return \array_sum($this->errors);
    }

    /**
     * Gets the error entries.
     *
     * @return SwissPostResultType
     */
    public function getErrors(): array
    {
        return $this->errors;
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
     * Gets the total number of valid entries.
     */
    public function getValidCount(): int
    {
        return \array_sum($this->valids);
    }

    /**
     * Gets the validity date.
     */
    public function getValidity(): ?\DateTimeInterface
    {
        return $this->validity;
    }

    /**
     * Gets the valid entries.
     *
     * @return SwissPostResultType
     */
    public function getValids(): array
    {
        return $this->valids;
    }

    /**
     * Gets the overwrite option.
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
     * Sets the overwrite option.
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
    public function setValidity(\DateTimeInterface $validity): self
    {
        $this->validity = $validity;

        return $this;
    }

    /**
     * @psalm-param 'state'|'city'|'street' $key
     */
    private function add(bool $valid, string $key): bool
    {
        $valid ? $this->valids[$key]++ : $this->errors[$key]++;

        return $valid;
    }
}
