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
    use ComparableSortTrait;

    /**
     * Returns the first element of the given array that satisfies the given predicate.
     *
     * @template TKey of array-key
     * @template TValue
     *
     * @param array<TKey, TValue>          $array
     * @param \Closure(TKey, TValue): bool $p
     *
     * @return TValue|null
     */
    public function findFirst(array $array, \Closure $p): mixed
    {
        if ([] === $array) {
            return null;
        }

        foreach ($array as $key => $value) {
            if ($p($key, $value)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Gets values from a single column in the input array.
     */
    public function getColumn(array $values, string|int $key): array
    {
        return \array_column($values, $key);
    }

    /**
     * Gets filtered values of a single column.
     *
     * @psalm-param int<0,2> $mode
     */
    public function getColumnFilter(array $values, string|int $key, ?callable $callback = null, int $mode = 0): array
    {
        return \array_filter($this->getColumn($values, $key), $callback, $mode);
    }

    /**
     * Gets the maximum value of a single column.
     *
     * @psalm-template TValue of int|float
     *
     * @psalm-param TValue $default
     *
     * @psalm-return TValue
     */
    public function getColumnMax(array $values, string|int $key, int|float $default = 0.0): int|float
    {
        if ([] === $values) {
            return $default;
        }

        /** @psalm-var non-empty-array<TValue> $values */
        $values = $this->getColumn($values, $key);

        /** @psalm-var TValue */
        return \max($values);
    }

    /**
     * Gets the sum of a single column.
     *
     * @psalm-template TValue of int|float
     *
     * @psalm-param TValue $default
     *
     * @psalm-return TValue
     */
    public function getColumnSum(array $values, string|int $key, int|float $default = 0.0): int|float
    {
        if ([] === $values) {
            return $default;
        }
        $values = $this->getColumn($values, $key);

        /** @psalm-var TValue */
        return \array_sum($values);
    }

    /**
     * Gets filtered values.
     *
     * @template T
     *
     * @param array<T|null> $values   the values to filter
     * @param callable|null $callback the callback function to use. If no callback is supplied, all empty entries
     *                                of array will be removed.
     * @param int           $mode     a flag determining what arguments are sent to callback:
     *                                <ul>
     *                                <li>0 - pass the value as the only argument</li>
     *                                <li>ARRAY_FILTER_USE_KEY - pass key as the only argument to callback instead
     *                                of the value</li>
     *                                <li>ARRAY_FILTER_USE_BOTH - pass both value and key as arguments to callback
     *                                instead of the value</li>
     *                                </ul>
     *
     * @return T[]
     */
    public function getFiltered(array $values, ?callable $callback = null, int $mode = 0): array
    {
        // @phpstan-ignore-next-line
        return \array_filter($values, $callback, $mode);
    }

    /**
     * Sort the given array.
     *
     * @template T
     *
     * @param T[] $array
     *
     * @return T[] the sorted array
     */
    public function getSorted(array $array, int $flags = \SORT_REGULAR): array
    {
        if ([] !== $array) {
            \sort($array, $flags);
        }

        return $array;
    }

    /**
     * Gets filtered and uniques values.
     *
     * @template T
     *
     * @param array<T|null> $values   the values to filter and to get unique values for
     * @param callable|null $callback the callback function to use. If no callback is supplied, all empty entries
     *                                of array will be removed.
     * @param int           $mode     a flag determining what arguments are sent to callback:
     *                                <ul>
     *                                <li>0 - pass the value as the only argument</li>
     *                                <li>ARRAY_FILTER_USE_KEY - pass key as the only argument to callback instead
     *                                of the value</li>
     *                                <li>ARRAY_FILTER_USE_BOTH - pass both value and key as arguments to callback
     *                                instead of the value</li>
     *                                </ul>
     * @param int           $flags    the flags to be used to modify the comparison behavior
     *
     * @return T[]
     *
     * @psalm-param 0|1|2 $mode
     * @psalm-param 0|1|2|5 $flags
     */
    public function getUniqueFiltered(
        array $values,
        ?callable $callback = null,
        int $mode = 0,
        int $flags = \SORT_REGULAR
    ): array {
        $values = $this->getFiltered($values, $callback, $mode);

        return \array_unique($values, $flags);
    }

    /**
     * Gets merged and uniques values.
     *
     * @template T
     *
     * @param T[] $first  the first array to merge
     * @param T[] $second the second array to merge
     * @param int $flags  the flags to be used to modify the comparison behavior
     *
     * @return T[]
     *
     * @psalm-param 0|1|2|5 $flags
     */
    public function getUniqueMerged(array $first, array $second, int $flags = \SORT_STRING): array
    {
        return \array_unique(\array_merge($first, $second), $flags);
    }
}
