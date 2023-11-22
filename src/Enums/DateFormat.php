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

namespace App\Enums;

use App\Interfaces\EnumConstantsInterface;
use App\Interfaces\EnumSortableInterface;

/**
 * The enumeration used to format dates and times.
 *
 * @implements EnumSortableInterface<DateFormat>
 * @implements EnumConstantsInterface<int>
 */
enum DateFormat: int implements EnumConstantsInterface, EnumSortableInterface
{
    /**
     * Completely specified style (Tuesday, April 12, 1952 AD or 3:30:42pm PST).
     *
     * @see \IntlDateFormatter::FULL;
     */
    case FULL = 0;

    /**
     * Long style (January 12, 1952 or 3:30:32pm).
     *
     * @see \IntlDateFormatter::LONG;
     */
    case LONG = 1;

    /**
     * Medium style (Jan 12, 1952).
     *
     * @see \IntlDateFormatter::MEDIUM;
     */
    case MEDIUM = 2;

    /**
     * Do not include this element.
     *
     * @see \IntlDateFormatter::NONE;
     */
    case NONE = -1;

    /**
     * Most abbreviated style, only essential data (12/13/52 or 3:30pm).
     *
     * @see \IntlDateFormatter::SHORT;
     */
    case SHORT = 3;

    /**
     * Gets this enumeration as constants.
     */
    public static function constants(): array
    {
        return \array_reduce(
            self::cases(),
            /** @psalm-param array<string, int> $choices */
            static fn (array $choices, self $type): array => $choices + ['FORMAT_' . $type->name => $type->value],
            [],
        );
    }

    /**
     * @return DateFormat[]
     */
    public static function sorted(): array
    {
        return [
            self::NONE,
            self::FULL,
            self::LONG,
            self::MEDIUM,
            self::SHORT,
        ];
    }
}
