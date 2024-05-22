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
trait ComparableSortTrait
{
    /**
     * Sort the given array of comparable.
     *
     * @template TKey of array-key
     * @template TValue of ComparableInterface
     *
     * @param array<TKey, TValue> $values
     *
     * @return array<TKey, TValue>
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
