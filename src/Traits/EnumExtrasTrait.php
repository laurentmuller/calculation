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

    public function getExtraBool(string $key, bool $default = false, bool $throwOnMissingExtra = false): bool
    {
        /** @phpstan-var bool|null $value */
        $value = $this->getExtra($key, $throwOnMissingExtra);

        return \is_bool($value) ? $value : $default;
    }

    /**
     * @template TEnum of \UnitEnum
     *
     * @phpstan-param TEnum $default
     *
     * @phpstan-return TEnum
     */
    public function getExtraEnum(string $key, \UnitEnum $default, bool $throwOnMissingExtra = false): \UnitEnum
    {
        /** @phpstan-var TEnum|null $value */
        $value = $this->getExtra($key, $throwOnMissingExtra);

        return $value instanceof $default ? $value : $default;
    }

    public function getExtraFloat(string $key, float $default = 0.0, bool $throwOnMissingExtra = false): float
    {
        /** @phpstan-var float|null $value */
        $value = $this->getExtra($key, $throwOnMissingExtra);

        return \is_float($value) ? $value : $default;
    }

    public function getExtraInt(string $key, int $default = 0, bool $throwOnMissingExtra = false): int
    {
        /** @phpstan-var int|null $value */
        $value = $this->getExtra($key, $throwOnMissingExtra);

        return \is_int($value) ? $value : $default;
    }

    public function getExtraString(string $key, string $default = '', bool $throwOnMissingExtra = false): string
    {
        /** @phpstan-var string|null $value */
        $value = $this->getExtra($key, $throwOnMissingExtra);

        return \is_string($value) ? $value : $default;
    }
}
