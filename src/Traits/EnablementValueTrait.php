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

namespace App\Traits;

/**
 * Trait to validate enabled/disabled values.
 */
trait EnablementValueTrait
{
    private const array DISABLED_VALUES = ['false', 'off', 'no', 'disabled', 'not enabled'];
    private const array ENABLED_VALUES = ['true', 'on', 'yes', 'enabled', 'supported', 'active'];

    /**
     * Returns if the given value represents a disabled value, ignore case consideration.
     */
    public function isDisabledValue(string $value): bool
    {
        return \in_array(\strtolower($value), self::DISABLED_VALUES, true);
    }

    /**
     * Returns if the given value represents an enabled value, ignore case consideration.
     */
    public function isEnabledValue(string $value): bool
    {
        return \in_array(\strtolower($value), self::ENABLED_VALUES, true);
    }
}
