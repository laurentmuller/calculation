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

use App\Utils\FormatUtils;

/**
 * Trait for mathematical functions.
 */
trait MathTrait
{
    /**
     * Round fractions up with 2 digits.
     */
    protected function ceil(float $value): float
    {
        return \ceil($value * 100.0) / 100.0;
    }

    /**
     * Round fractions down with 2 digits.
     */
    protected function floor(float $value): float
    {
        return \floor($value * 100.0) / 100.0;
    }

    /**
     * Checks if the given value contains the bit mask.
     *
     * @param int $value the value to be tested
     * @param int $mask  the bit mask
     *
     * @return bool true if set
     */
    protected function isBitSet(int $value, int $mask): bool
    {
        return $mask === ($mask & $value);
    }

    /**
     * Returns if the two float values are equals.
     *
     * @param float $val1      the first value to compare to
     * @param float $val2      the second value to compare to
     * @param int   $precision the optional number of decimal digits to round to
     *
     * @return bool true if values are equals
     */
    protected function isFloatEquals(float $val1, float $val2, int $precision = FormatUtils::FRACTION_DIGITS): bool
    {
        return $this->round($val1, $precision) === $this->round($val2, $precision);
    }

    /**
     * Returns if the given float value is equal to zero.
     *
     * @param float $val       the value to be tested
     * @param int   $precision the optional number of decimal digits to round to
     *
     * @return bool true if the value is equal to zero
     */
    protected function isFloatZero(float $val, int $precision = FormatUtils::FRACTION_DIGITS): bool
    {
        return $this->isFloatEquals($val, 0.0, $precision);
    }

    /**
     * Returns the rounded value to the specified precision.
     *
     * @param ?float $val       the value to round
     * @param int    $precision the number of decimal digits to round to
     * @param int    $mode      the rounding mode
     *
     * @phpstan-param int<1,4> $mode
     *
     * @return float the rounded value or 0.0 if the value is null
     */
    protected function round(
        ?float $val,
        int $precision = FormatUtils::FRACTION_DIGITS,
        int $mode = \PHP_ROUND_HALF_DOWN
    ): float {
        return null === $val ? 0.0 : \round($val, $precision, $mode);
    }

    /**
     * Execute a safe division operation. Returns the default value when the divisor is equal to 0.
     *
     * @param float $default the default value to return when divisor is equal to 0
     *
     * @return float the division result
     */
    protected function safeDivide(float|int $dividend, float|int $divisor, float $default = 0.0): float
    {
        $result = \fdiv($dividend, $divisor);

        return \is_finite($result) ? $result : $default;
    }

    /**
     * Ensure that the given value is within the given inclusive range.
     *
     * @param int|float $value the value to be tested
     * @param int|float $min   the minimum value allowed (inclusive)
     * @param int|float $max   the maximum value allowed (inclusive)
     *
     * @return int|float checked value
     *
     * @phpstan-return ($value is float ? float : int)
     */
    protected function validateRange(int|float $value, int|float $min, int|float $max): int|float
    {
        if ($value < $min) {
            return $min;
        } elseif ($value > $max) {
            return $max;
        }

        return $value;
    }
}
