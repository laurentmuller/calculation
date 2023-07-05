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
     * Any additional keys will be used for grouping the next set of sub-arrays.
     *
     * @psalm-template T of array|object
     *
     * @psalm-param array<T>              $array
     * @psalm-param string|int|callable(T):array-key $key
     * @psalm-param string|int|callable(T):array-key ...$others
     */
    public function groupBy(array $array, string|int|callable $key, string|int|callable ...$others): array
    {
        $result = [];
        foreach ($array as $value) {
            $entry = $this->getGroupKey($value, $key);
            $result[$entry][] = $value;
        }

        $num_args = \func_num_args();
        if ($num_args > 2) {
            $function = [self::class, __FUNCTION__];
            $slice_args = \array_slice(\func_get_args(), 2, $num_args);
            foreach ($result as $groupKey => $value) {
                $params = \array_merge([$value], $slice_args);
                $result[$groupKey] = (array) \call_user_func_array($function, $params); // @phpstan-ignore-line
            }
        }

        return $result;
    }

    private function getGroupKey(array|object $value, string|int|callable $key): string|int
    {
        if (\is_callable($key)) {
            /** @psalm-var array-key $entry */
            $entry = $key($value);
        } elseif (\is_array($value)) {
            /** @psalm-var array-key $entry */
            $entry = $value[$key];
        } else { // object
            /** @psalm-var array-key $entry */
            $entry = $value->{$key};
        }

        return $entry;
    }
}
