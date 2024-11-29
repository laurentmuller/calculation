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
     * Sort, in reverse order, the given array of comparable.
     *
     * @template TKey of array-key
     * @template TValue of ComparableInterface
     *
     * @param array<TKey, TValue> $values        the values to sort
     * @param bool                $preserve_keys if set to true, the keys are preserved
     *
     * @return array<TKey, TValue> the sorted values in reverse order
     */
    public function getReversedSortedComparable(array $values, bool $preserve_keys = true): array
    {
        if ([] === $values) {
            return [];
        }

        return \array_reverse($this->getSortedComparable($values), $preserve_keys);
    }

    /**
     * Sort the given array of comparable.
     *
     * @template TKey of array-key
     * @template TValue of ComparableInterface
     *
     * @param array<TKey, TValue> $values the values to sort
     *
     * @return array<TKey, TValue> the sorted values
     */
    public function getSortedComparable(array $values): array
    {
        if ([] === $values) {
            return [];
        }
        \uasort($values, static fn (ComparableInterface $a, ComparableInterface $b): int => $a->compare($b));

        return $values;
    }
}
