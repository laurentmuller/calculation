<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
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
     * @var callable
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
     * Gets the callback used to format a semestre.
     *
     * @return callable|null the callback, if set; null otherwise
     */
    public function getFormatter(): ?callable
    {
        return $this->formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle($value = null): ?string
    {
        if (\is_int($value)) {
            return $this->formatSemester($value);
        } else {
            return parent::getTitle($value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getValue(array $row)
    {
        $value = parent::getValue($row);
        if (\is_int($value)) {
            return (int) \ceil($value / 6);
        }

        return $value;
    }

    /**
     * Sets callback used to format a semestre.
     *
     * The function receive the semestre (1 or 2 ) as parameter and must return a string.
     *
     * @param callable|null $formatter the callback to set; null to use default
     */
    public function setFormatter(?callable $formatter): self
    {
        $this->formatter = $formatter;

        return $this;
    }

    /**
     * Formats the semester.
     *
     * @param int $semestre the semester (normally 1 or 2) to format
     *
     * @return string|null the formatted semester
     */
    private function formatSemester(int $semestre): ?string
    {
        if ($this->formatter) {
            return \call_user_func($this->formatter, $semestre);
        } else {
            switch ($semestre) {
                case 1:
                    return '1er semestre'; // 1st semester
                case 2:
                    return '2ème semestre'; // 2nd semester
                default:
                    return parent::getTitle();
            }
        }
    }
}
