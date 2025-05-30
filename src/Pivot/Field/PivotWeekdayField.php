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

use App\Utils\DateUtils;

/**
 * The pivot field that map the week day values (1...7) to the wek day names (monday, tuesday, etc...).
 */
class PivotWeekdayField extends PivotDateField
{
    /**
     * The weekday names.
     *
     * @var string[]
     */
    private readonly array $names;

    /**
     * @param string  $name  the field name
     * @param ?string $title the field title
     * @param bool    $short true to display the short day name, false to display the day name
     */
    public function __construct(protected string $name, protected ?string $title = null, bool $short = false)
    {
        parent::__construct($name, self::PART_WEEK_DAY, $title);

        /** @phpstan-var int<1, max> $time */
        $time = \strtotime('this week');
        $firstDay = \strtolower(\date('l', $time));
        $this->names = $short ? DateUtils::getShortWeekdays($firstDay) : DateUtils::getWeekdays($firstDay);
    }

    #[\Override]
    public function getDisplayValue(mixed $value): mixed
    {
        if (\is_int($value) && \array_key_exists($value, $this->names)) {
            return $this->names[$value];
        }

        return parent::getDisplayValue($value);
    }
}
