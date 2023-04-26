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
     * @psalm-param array<array-key, mixed>              $array
     * @psalm-param string|int|callable(mixed):array-key $key
     * @psalm-param string|int|callable(mixed):array-key ...$others
     */
    public function groupBy(array $array, string|int|callable $key, string|int|callable ...$others): array
    {
        $result = [];
        /** @psalm-var object|array $value */
        foreach ($array as $value) {
            if (\is_callable($key)) {
                $entry = $key($value);
            } elseif (\is_object($value)) {
                /** @psalm-var array-key $entry */
                $entry = $value->{$key};
            } else { // array
                /** @psalm-var array-key $entry */
                $entry = $value[$key];
            }
            $result[$entry][] = $value;
        }
        if (\func_num_args() > 2) {
            $args = \func_get_args();
            /** @psalm-var callable(mixed):array-key $callback */
            $callback = [self::class, __FUNCTION__];
            /** @psalm-param string|int $groupKey */
            foreach ($result as $groupKey => $value) {
                $params = \array_merge([$value], \array_slice($args, 2, \func_num_args()));
                /** @psalm-var array|object $value */
                $value = \call_user_func_array($callback, $params);
                $result[$groupKey] = $value;
            }
        }

        return $result;
    }
}
