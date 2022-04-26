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
 * Trait for mathematical functions.
 */
trait MathTrait
{
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
    protected function isFloatEquals(float $val1, float $val2, int $precision = 2): bool
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
    protected function isFloatZero(float $val, int $precision = 2): bool
    {
        return $this->isFloatEquals($val, 0.0, $precision);
    }

    /**
     * Returns the rounded value to the specified precision.
     *
     * @param float|null $val       the value to round
     * @param int        $precision the optional number of decimal digits to round to
     *
     * @return float the rounded value or 0 if value is empty
     */
    protected function round(?float $val, int $precision = 2): float
    {
        return empty($val) ? 0.0 : \round($val, $precision);
    }

    /**
     * Execute a safe division operation. Returns the default value when the divisor is equal to 0.
     *
     * @param float $dividend the dividend (numerator)
     * @param float $divisor  the divisor (denominator)
     * @param float $default  the default value to return when divisor is equal to 0
     *
     * @return float the division result
     */
    protected function safeDivide(float $dividend, float $divisor, float $default = 0.0): float
    {
        return empty($divisor) ? $default : $dividend / $divisor;
    }

    /**
     * Ensure that the given value is within the given range.
     *
     * @param int $value the value to be tested
     * @param int $min   the minimum value allowed (inclusive)
     * @param int $max   the maximum value allowed (inclusive)
     *
     * @return int the checked value
     */
    protected function validateIntRange(int $value, int $min, int $max): int
    {
        if ($value < $min) {
            return $min;
        } elseif ($value > $max) {
            return $max;
        } else {
            return $value;
        }
    }
}
