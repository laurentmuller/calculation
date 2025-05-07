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

namespace App\Pivot\Field;

/**
 * The pivot field that extracts quarter (1 or 4).
 */
class PivotQuarterField extends PivotDateField
{
    /**
     * @var callable(int): string|null
     */
    private $formatter;

    public function __construct(protected string $name, protected ?string $title = null)
    {
        parent::__construct($name, self::PART_MONTH, $title);
    }

    #[\Override]
    public function getDisplayValue(mixed $value): string
    {
        return $this->formatQuarter((int) $value);
    }

    /**
     * Gets the callback used to format a quarter.
     *
     * @phpstan-return callable(int): string|null
     */
    public function getFormatter(): ?callable
    {
        return $this->formatter;
    }

    /**
     * Sets callback used to format a quarter.
     *
     * The function receives the quarter (1 to 4) as a parameter and must return a string.
     *
     * @param ?callable $formatter the optional callback
     *
     * @phpstan-param callable(int): string|null $formatter
     */
    public function setFormatter(?callable $formatter): self
    {
        $this->formatter = $formatter;

        return $this;
    }

    #[\Override]
    protected function doGetValue(\DateTimeInterface $date): int
    {
        $value = parent::doGetValue($date);

        return (int) \ceil($value / 3);
    }

    /**
     * Formats the quarter.
     *
     * @param int $quarter the quarter (1 to 4) to format
     */
    private function formatQuarter(int $quarter): string
    {
        if (\is_callable($this->formatter)) {
            return \call_user_func($this->formatter, $quarter);
        }

        return match ($quarter) {
            1 => '1st quarter',
            2 => '2nd quarter',
            3 => '3rd quarter',
            4 => '4th quarter',
            default => (string) $quarter,
        };
    }
}
