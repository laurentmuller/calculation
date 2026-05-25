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

use Symfony\Component\Clock\DatePoint;

/**
 * The pivot field that extracts quarter (1 or 4).
 */
class PivotQuarterField extends PivotDateField
{
    /** @var \Closure(int): string */
    private readonly \Closure $formatter;

    /**
     * @param ?\Closure(int): string $formatter the optional callback formatter
     */
    public function __construct(string $name, ?string $title = null, ?\Closure $formatter = null)
    {
        parent::__construct($name, self::PART_MONTH, $title);
        $this->formatter = $formatter ?? self::getDefaultFormatter();
    }

    /**
     * Gets the default formatter.
     *
     * @return \Closure(int): string
     */
    public static function getDefaultFormatter(): \Closure
    {
        return static fn (int $semester): string => match ($semester) {
            1 => '1st quarter',
            2 => '2nd quarter',
            3 => '3rd quarter',
            4 => '4th quarter',
            default => throw new \InvalidArgumentException(\sprintf('Invalid quarter value: %d, allowed values [1..4].', $semester))
        };
    }

    /**
     * @throws \InvalidArgumentException if the value is not between 1 and 4 inclusive
     */
    #[\Override]
    public function getDisplayValue(mixed $value): string
    {
        return \call_user_func($this->formatter, (int) $value);
    }

    /**
     * Gets the callback used to format a quarter.
     *
     * @return \Closure(int): string
     */
    public function getFormatter(): \Closure
    {
        return $this->formatter;
    }

    #[\Override]
    protected function getDateValue(DatePoint $date): int
    {
        $value = parent::getDateValue($date);

        return (int) \ceil($value / 3);
    }
}
