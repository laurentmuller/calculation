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

use App\Pivot\Formatter\FormatterInterface;
use App\Pivot\Formatter\SemesterFormatter;
use Symfony\Component\Clock\DatePoint;

/**
 * The Pivot field that extracts semester (1 or 2).p.
 */
class PivotSemesterField extends PivotDateField
{
    public function __construct(string $name, ?string $title = null, ?FormatterInterface $formatter = null)
    {
        parent::__construct($name, self::PART_MONTH, $title, $formatter ?? new SemesterFormatter());
    }

    #[\Override]
    protected function getDateValue(DatePoint $date): int
    {
        return (int) \ceil(parent::getDateValue($date) / 6);
    }
}
