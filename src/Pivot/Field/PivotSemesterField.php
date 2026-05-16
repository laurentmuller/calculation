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
 * The Pivot field that extracts semester (1 or 2).p.
 */
class PivotSemesterField extends PivotDateField
{
    /** @var (\Closure(int): string)|null */
    private ?\Closure $formatter = null;

    public function __construct(string $name, ?string $title = null)
    {
        parent::__construct($name, self::PART_MONTH, $title);
    }

    #[\Override]
    public function getDisplayValue(mixed $value): string
    {
        return $this->formatSemester((int) $value);
    }

    /**
     * Gets the callback used to format a semestre.
     *
     * @return ?\Closure(int): string
     */
    public function getFormatter(): ?\Closure
    {
        return $this->formatter;
    }

    /**
     * Sets callback used to format a semestre.
     *
     * The function receives the semestre (1 or 2) as a parameter and must return a string.
     *
     * @param ?\Closure(int): string $formatter the optional callback
     */
    public function setFormatter(?\Closure $formatter): self
    {
        $this->formatter = $formatter;

        return $this;
    }

    #[\Override]
    protected function getDateValue(DatePoint $date): int
    {
        $value = parent::getDateValue($date);

        return (int) \ceil($value / 6);
    }

    /**
     * Formats the semester.
     *
     * @param int $semester the semester (1 or 2) to format
     */
    private function formatSemester(int $semester): string
    {
        if (\is_callable($this->formatter)) {
            return \call_user_func($this->formatter, $semester);
        }

        return match ($semester) {
            1 => '1st semester',
            2 => '2nd semester',
            default => (string) $semester,
        };
    }
}
