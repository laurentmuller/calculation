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

namespace App\Comparable;

/**
 * Trait to implement the <code>IComparable</code> interface.
 *
 * @author Laurent Muller
 *
 * @see \App\Comparable\IComparable
 */
trait ComparableTrait
{
    /**
     * @return int (-1, 0, 1)
     */
    abstract public function compare(IComparable $other): int;

    public function isEqual(IComparable $other): bool
    {
        return 0 === $this->compare($other);
    }

    public function isGreaterThan(IComparable $other): bool
    {
        return $this->compare($other) > 0;
    }

    public function isGreaterThanOrEqual(IComparable $other): bool
    {
        return $this->compare($other) >= 0;
    }

    public function isLessThan(IComparable $other): bool
    {
        return $this->compare($other) < 0;
    }

    public function isLessThanOrEqual(IComparable $other): bool
    {
        return $this->compare($other) <= 0;
    }

    /**
     * Sort an array of <code>IComparable</code>.
     *
     * @param array $array     the array to sort
     * @param bool  $ascending true to sort ascending, false to sort descending
     *
     * @throws \LogicException if objects can not be compared
     */
    public static function sort(array &$array, bool $ascending = true): void
    {
        $order = $ascending ? 1 : -1;
        \usort($array, function (IComparable $a, IComparable $b) use ($order) {
            return $order * $a->compare($b);
        });
    }
}
