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
     *
     * @param string $name  the field name
     * @param string $title the field title
     */
    public function __construct(string $name, ?string $title = null)
    {
        parent::__construct($name, self::PART_MONTH, $title);
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplayValue($value)
    {
        return $this->formatSemester((int) $value);
    }

    /**
     * Gets the callback used to format a semestre.
     *
     * @return callable(int): string|null the callback, if set; null otherwise
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
     * @param callable(int): string|null $formatter the callback to set; null to use default
     */
    public function setFormatter(?callable $formatter): self
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetValue(\DateTimeInterface $date)
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
            switch ($semester) {
                case 1:
                    return '1st semester';
                case 2:
                    return '2nd semester';
                default:
                    return (string) $semester;
            }
        }
    }
}
