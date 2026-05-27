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

use App\Pivot\Formatter\ArrayFormatter;
use App\Utils\DateUtils;

/**
 * The pivot field that maps month values (1...12) to month names (January, February, etc...).
 */
class PivotMonthField extends PivotDateField
{
    public function __construct(string $name, ?string $title = null)
    {
        parent::__construct($name, self::PART_MONTH, $title, new ArrayFormatter(DateUtils::getMonths()));
    }
}
