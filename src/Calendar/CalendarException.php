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

namespace App\Calendar;

/**
 * Calendar exception.
 */
class CalendarException extends \RuntimeException
{
    /**
     * @phpstan-param string|int ...$values
     */
    public static function format(string $format, mixed ...$values): self
    {
        return self::instance(\sprintf($format, ...$values));
    }

    public static function instance(string $message): self
    {
        return new self($message);
    }
}
