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

use Elao\Enum\ExtrasTrait;

/**
 * Extends the extra trait with typed values.
 */
trait EnumExtrasTrait
{
    use ExtrasTrait;

    public function getExtraBool(string $key, bool $default = false): bool
    {
        $value = $this->getExtra($key);
        if (null !== $value && !\is_bool($value)) {
            $value = \filter_var($value, \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE);
        }

        return \is_bool($value) ? $value : $default;
    }

    /**
     * @template TEnum of \UnitEnum
     *
     * @param TEnum $default
     *
     * @return TEnum
     */
    public function getExtraEnum(string $key, \UnitEnum $default): \UnitEnum
    {
        $value = $this->getExtra($key);

        return $value instanceof $default ? $value : $default;
    }

    public function getExtraFloat(string $key, float $default = 0.0): float
    {
        $value = $this->getExtra($key);

        return \is_numeric($value) ? (float) $value : $default;
    }

    public function getExtraInt(string $key, int $default = 0): int
    {
        $value = $this->getExtra($key);

        return \is_numeric($value) ? (int) $value : $default;
    }

    public function getExtraString(string $key, string $default = ''): string
    {
        $value = $this->getExtra($key);

        return \is_string($value) ? $value : $default;
    }
}
