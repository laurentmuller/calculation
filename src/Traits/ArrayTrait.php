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
        return \array_any($array, $callback);
    }

    /**
     * Returns the first element of the given array that satisfies the given predicate.
     *
     * @template TKey of array-key
     * @template TValue
     *
     * @param array<TKey, TValue> $array    the array that should be searched
     * @param callable            $callback The callback function to call to find a matching element.
     *                                      The first parameter contains the value ($value), the second parameter
     *                                      contains the corresponding key ($key).
     *
     * @phpstan-param callable(TValue, TKey): bool $callback
     *
     * @return TValue|null a value if there is at least one element for which callback returns true, null otherwise
     */
    public function findFirst(array $array, callable $callback): mixed
    {
        return \array_find($array, $callback);
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

        return \max($values);
    }

    /**
     * Gets the sum of a single column.
     *
     * @phpstan-template TValue of int|float
     *
     * @phpstan-param TValue $default
     *
     * @phpstan-return ($default is int ? int : float)
     */
    public function getColumnSum(array $values, string|int $key, int|float $default = 0.0): int|float
    {
        if ([] === $values) {
            return $default;
        }
        $values = $this->getColumn($values, $key);

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
        /** @phpstan-var T[] */
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
     * Maps each keys and values of the given array to a string using the given callback.
     *
     * @template TKey of array-key
     * @template TValue
     *
     * @param array<TKey, TValue>            $array    the array to map
     * @param callable(TKey, TValue): string $callable the callback to get the single value
     *
     * @return string[] the mapped array
     */
    public function mapKeyAndValue(array $array, callable $callable): array
    {
        return \array_map($callable, \array_keys($array), \array_values($array));
    }

    /**
     * Maps the given array of each key and value pair to a single value using the given callback.
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
            static fn (array $carry, $value): array => $carry + $callable($value),
            []
        );
    }

    /**
     * Filter the given array by removing the given element.
     *
     * @template T
     *
     * @param T[] $values the array to filter
     * @param T   $value  the value to remove
     *
     * @return T[] the array without the given element, if found
     */
    public function removeValue(array $values, mixed $value): array
    {
        return \array_filter($values, static fn (mixed $item): bool => $item !== $value);
    }
}
