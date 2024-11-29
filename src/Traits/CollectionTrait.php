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
 * Trait for collections.
 */
trait CollectionTrait
{
    use ComparableTrait;

    /**
     * Sort, in reverse order, the given collection of comparable.
     *
     * @template TKey of array-key
     * @template TValue of ComparableInterface
     *
     * @param Collection<TKey, TValue> $collection    the collection to sort
     * @param bool                     $preserve_keys if set to true, the keys are preserved
     *
     * @return array<TKey, TValue> the sorted values in reverse order
     */
    public function getReversedSortedCollection(Collection $collection, bool $preserve_keys = true): array
    {
        if ($collection->isEmpty()) {
            return [];
        }

        return $this->getReversedSortedComparable($collection->toArray(), $preserve_keys);
    }

    /**
     * Sort the given collection of comparable.
     *
     * @template TKey of array-key
     * @template TValue of ComparableInterface
     *
     * @param Collection<TKey, TValue> $collection the collection to sort
     *
     * @return array<TKey, TValue> the sorted values
     */
    public function getSortedCollection(Collection $collection): array
    {
        if ($collection->isEmpty()) {
            return [];
        }

        return $this->getSortedComparable($collection->toArray());
    }
}
