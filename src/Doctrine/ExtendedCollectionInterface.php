<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
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
