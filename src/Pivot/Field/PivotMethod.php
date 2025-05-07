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

/**
 * Method enumeration indicates how values are converted.
 */
enum PivotMethod
{
    /**
     * Convert values as float.
     */
    case FLOAT;

    /**
     * Convert values as integer.
     */
    case INTEGER;

    /**
     * Convert values as string.
     */
    case STRING;

    /**
     * Convert the given value.
     *
     * @phpstan-param scalar|null $value
     */
    public function convert(mixed $value): float|int|string
    {
        return match ($this) {
            self::FLOAT => (float) $value,
            self::INTEGER => (int) $value,
            self::STRING => (string) $value,
        };
    }
}
