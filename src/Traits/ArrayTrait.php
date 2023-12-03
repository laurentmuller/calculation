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
 * Trait for array functions.
 */
trait ArrayTrait
{
    /**
     * Gets the values from a single column in the input array.
     */
    public function getColumn(array $values, string|int $key): array
    {
        return \array_column($values, $key);
    }

    /**
     * Gets the filtered values of the given column.
     *
     * @psalm-param int<0,2> $mode
     */
    public function getColumnFilter(array $values, string|int $key, callable $callback = null, int $mode = 0): array
    {
        return \array_filter($this->getColumn($values, $key), $callback, $mode);
    }

    /**
     * Gets the maximum of the given column.
     *
     * @psalm-suppress ArgumentTypeCoercion
     */
    public function getColumnMax(array $values, string|int $key, float $default = 0.0): float
    {
        return [] === $values ? $default : (float) \max($this->getColumn($values, $key));
    }

    /**
     * Gets the sum of the given column.
     */
    public function getColumnSum(array $values, string|int $key, float $default = 0.0): float
    {
        return [] === $values ? $default : (float) \array_sum($this->getColumn($values, $key));
    }

    /**
     * Gets filtered and uniques values.
     *
     * @param array         $values   the values to filter and to get unique values for
     * @param callable|null $callback the callback function to use. If no callback is supplied, all empty entries
     *                                of array will be removed.
     * @param int           $mode     a flag determining what arguments are sent to callback:
     *                                <ul>
     *                                <li>ARRAY_FILTER_USE_KEY - pass key as the only argument to callback instead
     *                                of the value</li>
     *                                <li>ARRAY_FILTER_USE_BOTH - pass both value and key as arguments to callback
     *                                instead of the value</li>
     *                                </ul>
     *
     * @psalm-template T
     *
     * @psalm-param array<T|null> $values
     * @psalm-param 0|1|2 $mode
     *
     * @psalm-return T[]
     */
    public function getUniqueFiltered(array $values, callable $callback = null, int $mode = 0): array
    {
        if (\is_callable($callback)) {
            // @phpstan-ignore-next-line
            return \array_unique(\array_filter($values, $callback, $mode));
        }

        // @phpstan-ignore-next-line
        return \array_unique(\array_filter($values, mode: $mode));
    }
}
