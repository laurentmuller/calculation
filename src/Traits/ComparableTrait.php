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

use App\Interfaces\ComparableInterface;

/**
 * Trait to compare {@link ComparableInterface} values.
 */
trait ComparableTrait
{
    /**
     * Sort the given array of comparable.
     *
     * @template TKey of array-key
     * @template TValue of ComparableInterface
     *
     * @param array<TKey, TValue> $values the values to sort
     *
     * @return array<TKey, TValue> $values the sorted values
     */
    public function sortComparable(array $values): array
    {
        if ([] !== $values) {
            \uasort($values, static fn (ComparableInterface $a, ComparableInterface $b): int => $a->compare($b));
        }

        return $values;
    }

    /**
     * Sort the given array of comparable in reverse order.
     *
     * @template TKey of array-key
     * @template TValue of ComparableInterface
     *
     * @param array<TKey, TValue> $values the values to sort
     *
     * @return array<TKey, TValue> $values the sorted values, in reverse order
     */
    public function sortReverseComparable(array $values): array
    {
        if ([] !== $values) {
            \uasort($values, static fn (ComparableInterface $a, ComparableInterface $b): int => $b->compare($a));
        }

        return $values;
    }
}
