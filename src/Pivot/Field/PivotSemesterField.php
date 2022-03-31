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

namespace App\Pivot\Field;

/**
 * Pivot field that extract semester (1 or 2).
 *
 * @author Laurent Muller
 */
class PivotSemesterField extends PivotDateField
{
    /**
     * @var callable(int): string|null
     */
    private $formatter;

    /**
     * Constructor.
     */
    public function __construct(protected string $name, protected ?string $title = null)
    {
        parent::__construct($name, self::PART_MONTH, $title);
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayValue($value): string
    {
        return $this->formatSemester((int) $value);
    }

    /**
     * Gets the callback used to format a semestre.
     *
     * @pslam-return callable(int): string|null the callback, if set; null otherwise
     */
    public function getFormatter(): ?callable
    {
        return $this->formatter;
    }

    /**
     * Sets callback used to format a semestre.
     *
     * The function receive the semestre (1 or 2 ) as parameter and must return a string.
     *
     * @param ?callable $formatter the optional callback
     * @psalm-param callable(int): string|null $formatter
     */
    public function setFormatter(?callable $formatter): self
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetValue(\DateTimeInterface $date): int
    {
        $value = parent::doGetValue($date);

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
        } else {
            return match ($semester) {
                1 => '1st semester',
                2 => '2nd semester',
                default => (string) $semester,
            };
        }
    }
}
