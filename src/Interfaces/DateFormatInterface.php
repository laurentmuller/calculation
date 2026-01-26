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

namespace App\Interfaces;

/**
 * Contains constants for date formats.
 */
interface DateFormatInterface
{
    /**
     * The map between format names and values.
     */
    public const array DATE_FORMATS = [
        self::FORMAT_NONE => \IntlDateFormatter::NONE,
        self::FORMAT_FULL => \IntlDateFormatter::FULL,
        self::FORMAT_LONG => \IntlDateFormatter::LONG,
        self::FORMAT_MEDIUM => \IntlDateFormatter::MEDIUM,
        self::FORMAT_SHORT => \IntlDateFormatter::SHORT,
    ];

    /**
     * The full date or time format.
     */
    final public const string FORMAT_FULL = 'full';

    /**
     * The long date or time format.
     */
    final public const string FORMAT_LONG = 'long';

    /**
     * The medium date or time format.
     */
    final public const string FORMAT_MEDIUM = 'medium';

    /**
     * The none date or time format.
     */
    final public const string FORMAT_NONE = 'none';

    /**
     * The short date or time format.
     */
    final public const string FORMAT_SHORT = 'short';
}
