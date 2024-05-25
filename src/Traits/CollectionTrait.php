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
    use ComparableSortTrait;

    /**
     * Returns the first element of the given collection that satisfies the given predicate.
     *
     * @template TKey of array-key
     * @template TValue
     *
     * @param Collection<TKey, TValue>     $collection
     * @param \Closure(TKey, TValue): bool $p
     *
     * @return TValue|null
     *
     * @psalm-suppress InvalidArgument
     */
    public function findFirst(Collection $collection, \Closure $p): mixed
    {
        if ($collection->isEmpty()) {
            return null;
        }

        /** @psalm-var TValue|null */
        return $collection->findFirst($p);
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
