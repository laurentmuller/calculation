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
 * Pivot field that map the week day values (1...7) to the wek day names (monday, tuesday, etc...).
 *
 * @author Laurent Muller
 */
class PivotWeekdayField extends PivotDateField
{
    /**
     * The weekday names.
     *
     * @var string[]
     */
    private $names;

    /**
     * Constructor.
     *
     * @param string $name  the field name
     * @param bool   $short true to display the short day name, false to display the day name
     */
    public function __construct(string $name, bool $short = false)
    {
        parent::__construct($name, self::PART_WEEK_DAY);

        $firstDay = \date('l', \strtotime('this week'));
        $this->names = $short ? DateUtils::getShortWeekdays($firstDay) : DateUtils::getWeekdays($firstDay);
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
