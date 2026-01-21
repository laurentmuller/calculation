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
use Doctrine\Common\Collections\Collection;

/**
 * Trait to sort collections of {@link ComparableInterface} values.
 */
trait CollectionTrait
{
    use ComparableTrait;

    /**
     * Sort the given collection of comparable and maintain index association.
     *
     * @template TKey of array-key
     * @template TValue of ComparableInterface
     *
     * @param Collection<TKey, TValue> $collection the collection to sort
     * @param bool                     $reverse    <code>true</code> to sort in descending (reverse) mode,
     *                                             <code>false</code> (default) to sort in ascending mode
     *
     * @return array<TKey, TValue> the sorted values
     */
    public function getSortedCollection(Collection $collection, bool $reverse = false): array
    {
        $values = $collection->toArray();
        if (\count($values) < 2) {
            return $values;
        }
        $this->sortComparable($values, $reverse);

        return $values;
    }
}
