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

namespace App\Traits;

use App\Interfaces\ComparableInterface;

/**
 * Trait to implement the <code>ComparableInterface</code> interface.
 *
 * @author Laurent Muller
 *
 * @see ComparableInterface
 */
trait ComparableTrait
{
    /**
     * Compare this instance with an other object.
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
    abstract public function compare(ComparableInterface $other): int;

    /**
     * Returns if this instance is equal to the other object.
     *
     * @param ComparableInterface $other the other object to compare to
     *
     * @return bool true if equal
     */
    public function isEqual(ComparableInterface $other): bool
    {
        return 0 === $this->compare($other);
    }

    /**
     * Returns if this instance is greather than to the other object.
     *
     * @param ComparableInterface $other the other object to compare to
     *
     * @return bool true if greather than
     */
    public function isGreaterThan(ComparableInterface $other): bool
    {
        return $this->compare($other) > 0;
    }

    /**
     * Returns if this instance is greather than or equal to the other object.
     *
     * @param ComparableInterface $other the other object to compare to
     *
     * @return bool true if greather than or equal
     */
    public function isGreaterThanOrEqual(ComparableInterface $other): bool
    {
        return $this->compare($other) >= 0;
    }

    /**
     * Returns if this instance is less (smaller) than to the other object.
     *
     * @param ComparableInterface $other the other object to compare to
     *
     * @return bool true if less than
     */
    public function isLessThan(ComparableInterface $other): bool
    {
        return $this->compare($other) < 0;
    }

    /**
     * Returns if this instance is less (smaller) than or equal to the other object.
     *
     * @param ComparableInterface $other the other object to compare to
     *
     * @return bool true if less than or equal
     */
    public function isLessThanOrEqual(ComparableInterface $other): bool
    {
        return $this->compare($other) <= 0;
    }

    /**
     * Sort an array of <code>ComparableInterface</code>.
     *
     * @param ComparableInterface[] $array     the array to sort
     * @param bool                  $ascending true to sort ascending, false to sort descending
     *
     * @throws \LogicException if objects can not be compared
     */
    public static function sort(array &$array, bool $ascending = true): void
    {
        $order = $ascending ? 1 : -1;
        \usort($array, function (ComparableInterface $a, ComparableInterface $b) use ($order) {
            return $order * $a->compare($b);
        });
    }
}
