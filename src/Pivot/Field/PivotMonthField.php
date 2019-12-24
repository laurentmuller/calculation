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

use App\Utils\DateUtils;

/**
 * Pivot field that map month values (1...12) to month names (january, february, etc...).
 *
 * @author Laurent Muller
 */
class PivotMonthField extends PivotDateField
{
    /**
     * The month names.
     *
     * @var string[]
     */
    private $names;

    /**
     * Constructor.
     *
     * @param string $name  the field name
     * @param bool   $short true to display the short month name, false to display the full month name
     */
    public function __construct(string $name, bool $short = false)
    {
        parent::__construct($name, self::PART_MONTH);
        $this->names = $short ? DateUtils::getShortMonths() : DateUtils::getMonths();
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle($value = null): ?string
    {
        if (\array_key_exists((int) $value, $this->names)) {
            return $this->names[$value];
        }

        // default
        return parent::getTitle($value);
    }
}
