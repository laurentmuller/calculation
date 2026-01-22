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
     * Sort the given array of comparable and maintain index association.
     *
     * @template TKey of array-key
     * @template TValue of ComparableInterface
     *
     * @param array<TKey, TValue> $values  the values to sort
     * @param bool                $reverse <code>true</code> to sort in descending (reverse) mode,
     *                                     <code>false</code> (default) to sort in ascending mode
     *
     * @return bool true if the given array contains more than one value
     */
    public function sortComparable(array &$values, bool $reverse = false): bool
    {
        if (\count($values) < 2) {
            return false;
        }
        if ($reverse) {
            return \uasort($values, static fn (ComparableInterface $a, ComparableInterface $b): int => $b->compare($a));
        }

        return \uasort($values, static fn (ComparableInterface $a, ComparableInterface $b): int => $a->compare($b));
    }
}
