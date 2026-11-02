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

use App\Utils\StringUtils;

/**
 * Trait to validate enabled/disabled values.
 *
 * @require-implements InfoServiceInterface
 */
trait EnablementValueTrait
{
    /** The disabled values. */
    private const array DISABLED_VALUES = ['false', 'off', 'no', 'disabled', 'not enabled'];

    /** The enabled values. */
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

    public function isNoneValue(string $value): bool
    {
        return StringUtils::equalIgnoreCase(self::NONE_VALUE, $value);
    }

    public function isNoValue(string $value): bool
    {
        return StringUtils::equalIgnoreCase(self::NO_VALUE, $value);
    }
}
