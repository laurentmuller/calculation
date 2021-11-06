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

namespace App\Interfaces;

/**
 * Defines a generalized comparison method that a class implements
 * to order or sort its instances.
 *
 * @author Laurent Muller
 */
interface ComparableInterface
{
    /**
     * Compare this object with an other object.
     *
     * <code>$this <&nbsp;  $other</code> =>  returns less then 0<br>
     * <code>$this == $other</code> =>  returns 0<br>
     * <code>$this >&nbsp;  $other</code> =>  returns greater then 0<br>
     *
     * @param ComparableInterface $other the other object to compare to
     *
     * @return int (-1, 0, 1)
     *
     * @throws \LogicException if the other object can not be compared
     */
    public function compare(self $other): int;

    /**
     * Returns if this instance is equal to the other object.
     *
     * @param ComparableInterface $other the other object to compare to
     *
     * @return bool true if equal
     */
    public function isEqual(self $other): bool;

    /**
     * Returns if this instance is greather than to the other object.
     *
     * @param ComparableInterface $other the other object to compare to
     *
     * @return bool true if greather than
     */
    public function isGreaterThan(self $other): bool;

    /**
     * Returns if this instance is greather than or equal to the other object.
     *
     * @param ComparableInterface $other the other object to compare to
     *
     * @return bool true if greather than or equal
     */
    public function isGreaterThanOrEqual(self $other): bool;

    /**
     * Returns if this instance is less (smaller) than to the other object.
     *
     * @param ComparableInterface $other the other object to compare to
     *
     * @return bool true if less than
     */
    public function isLessThan(self $other): bool;

    /**
     * Returns if this instance is less (smaller) than or equal to the other object.
     *
     * @param ComparableInterface $other the other object to compare to
     *
     * @return bool true if less than or equal
     */
    public function isLessThanOrEqual(self $other): bool;
}
