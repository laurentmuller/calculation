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
 * Defines a generalized comparison method that a class implements
 * to order or sort its instances.
 *
 * @author Laurent Muller
 */
interface IComparable
{
    /**
     * Compare this object with an other object.
     *
     * <code>$this <&nbsp;  $other</code> =>  returns less then 0<br>
     * <code>$this == $other</code> =>  returns 0<br>
     * <code>$this >&nbsp;  $other</code> =>  returns greater then 0<br>
     *
     * @param IComparable $other the other object to compare to
     *
     * @return int (-1, 0, 1)
     *
     * @throws \LogicException if the other object can not be compared
     */
    public function compare(self $other): int;

    /**
     * Compare if equal to other.
     *
     * @param IComparable $other
     */
    public function isEqual(self $other): bool;

    /**
     * Compare if greather that other.
     *
     * @param IComparable $other
     */
    public function isGreaterThan(self $other): bool;

    /**
     * Compare if greather that or equal to other.
     *
     * @param IComparable $other
     */
    public function isGreaterThanOrEqual(self $other): bool;

    /**
     * Compare if less (smaller) than other.
     *
     * @param IComparable $other
     */
    public function isLessThan(self $other): bool;

    /**
     * Compare if less (smaller) or equal to other.
     *
     * @param IComparable $other
     */
    public function isLessThanOrEqual(self $other): bool;
}
