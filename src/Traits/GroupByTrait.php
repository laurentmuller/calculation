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
 * Trait to group object or array.
 */
trait GroupByTrait
{
    /**
     * Groups an array by the given key.
     *
     * Any additional keys will be used for grouping the next set of subarrays.
     *
     * @psalm-template T of array|object
     *
     * @psalm-param T[]                              $array
     * @psalm-param string|int|callable(T):array-key $key
     * @psalm-param string|int|callable(T):array-key ...$others
     *
     * @psalm-return array<array-key, T|mixed>
     */
    public function groupBy(array $array, string|int|callable $key, string|int|callable ...$others): array
    {
        if ([] === $array) {
            return [];
        }

        $result = [];
        foreach ($array as $value) {
            $entry = $this->getGroupKey($value, $key);
            $result[$entry][] = $value;
        }
        if (\func_num_args() <= 2) {
            return $result;
        }

        /** @psalm-var callable(array): array $function */
        $function = [self::class, __FUNCTION__]; // @phpstan-ignore-line
        $slice_args = \array_slice(\func_get_args(), 2);
        foreach ($result as $groupKey => $value) {
            $params = \array_merge([$value], $slice_args);
            $result[$groupKey] = \call_user_func_array($function, $params);
        }

        return $result;
    }

    /**
     * @psalm-param string|int|(callable(mixed): array-key) $key
     */
    private function getGroupKey(array|object $value, string|int|callable $key): string|int
    {
        if (\is_callable($key)) {
            return $key($value);
        }

        if (\is_array($value)) {
            /** @psalm-var array-key */
            return $value[$key];
        }

        /** @psalm-var array-key */
        return $value->{$key}; // @phpstan-ignore property.dynamicName
    }
}
