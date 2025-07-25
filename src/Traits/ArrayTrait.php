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
     * Checks whether the callback returns <code>true</code> for any of the array elements.
     *
     * @template TKey of array-key
     * @template TValue
     *
     * @param array<TKey, TValue> $array    the array that should be searched
     * @param \Closure            $callback The callback function to call to check each element.
     *                                      The first parameter contains the value ($value), the second parameter
     *                                      contains the corresponding key ($key).
     *                                      If this function returns <code>true</code> (or a truthy value),
     *                                      <code>true</code> is returned immediately
     *                                      and the callback will not be called for further elements.
     *
     * @phpstan-param \Closure(TValue, TKey=): bool $callback
     *
     * @return bool <code>true</code> if there is at least one element for which the callback returns <code>true</code>,
     *              <code>false</code> otherwise
     */
    public function anyMatch(array $array, \Closure $callback): bool
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the first element of the given array that satisfies the given predicate.
     *
     * @template TKey of array-key
     * @template TValue
     *
     * @param array<TKey, TValue> $array    the array that should be searched
     * @param \Closure            $callback The callback function to call to find a matching element.
     *                                      The first parameter contains the value ($value), the second parameter
     *                                      contains the corresponding key ($key).
     *
     * @phpstan-param \Closure(TValue, TKey=): bool $callback
     *
     * @return TValue|null a value if there is at least one element for which callback returns <code>true</code>,
     *                     null otherwise
     */
    public function findFirst(array $array, \Closure $callback): mixed
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
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
     * @phpstan-param int<0,2> $mode
     */
    public function getColumnFilter(array $values, string|int $key, ?callable $callback = null, int $mode = 0): array
    {
        return $this->getFiltered($this->getColumn($values, $key), $callback, $mode);
    }

    /**
     * Gets the maximum value of a single column.
     *
     * @phpstan-template TValue of int|float
     *
     * @phpstan-param TValue $default
     *
     * @phpstan-return TValue
     */
    public function getColumnMax(array $values, string|int $key, int|float $default = 0.0): int|float
    {
        if ([] === $values) {
            return $default;
        }

        /** @phpstan-var non-empty-array<TValue> $values */
        $values = $this->getColumn($values, $key);

        /** @phpstan-var TValue */
        return \max($values);
    }

    /**
     * Gets the sum of a single column.
     *
     * @phpstan-template TValue of int|float
     *
     * @phpstan-param TValue $default
     *
     * @phpstan-return TValue
     */
    public function getColumnSum(array $values, string|int $key, int|float $default = 0.0): int|float
    {
        if ([] === $values) {
            return $default;
        }
        $values = $this->getColumn($values, $key);

        /** @phpstan-var TValue */
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
     * @phpstan-param 0|1|2 $mode
     * @phpstan-param 0|1|2|5 $flags
     *
     * @return T[]
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
     * @phpstan-param 0|1|2|5 $flags
     *
     * @return T[]
     */
    public function getUniqueMerged(array $first, array $second, int $flags = \SORT_STRING): array
    {
        return \array_unique(\array_merge($first, $second), $flags);
    }

    /**
     * Maps the given array to keys and values pairs using the given callback.
     *
     * @template TKey of array-key
     * @template TValue
     * @template TResult
     *
     * @param TValue[]                              $array    the array to map
     * @param callable(TValue):array<TKey, TResult> $callable the callback to get the key and the value
     *
     * @return array<TKey, TResult> the mapped array
     */
    public function mapToKeyValue(array $array, callable $callable): array
    {
        return \array_reduce(
            $array,
            /**
             * @phpstan-param array<TKey, TResult> $carry
             * @phpstan-param TValue $value
             */
            fn (array $carry, $value): array => $carry + $callable($value),
            []
        );
    }

    /**
     * Remove elements of the given array that are equal to the given value.
     *
     * @template T
     *
     * @param T[] $values the array to update
     * @param T   $value  the value to remove
     *
     * @return T[]
     */
    public function removeValue(array $values, mixed $value): array
    {
        return \array_filter($values, fn (mixed $item): bool => $item !== $value);
    }
}
