<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Doctrine;

use Doctrine\Common\Collections\Collection;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Class implementing this interface extends the doctrine collection.
 *
 * @author Laurent Muller
 */
interface ExtendedCollectionInterface extends Collection
{
    /**
     * Return a new collection with sorted result.
     *
     * @param string|PropertyPathInterface $field the field name or the property path to sort by
     */
    public function getSortedCollection($field): self;

    /**
     * Gets the sorted iterator.
     *
     * @param string|PropertyPathInterface $field the field name or the property path to sort by
     */
    public function getSortedIterator($field): \ArrayIterator;

    /**
     * Iteratively reduce the underlaying array to a single value using a callback function.
     *
     * If the array is empty and initial is not passed, reduce returns null.
     *
     * @param callable $callback the callback with the follwowing signature: <code>callback(mixed $carry, mixed $item): mixed</code>
     * @param mixed    $initial  if the optional initial is available, it will be used at the beginning of the process,
     *                           or as a final result in case the array is empty
     *
     * @return mixed the resulting value
     */
    public function reduce(callable $callback, $initial = null);
}
