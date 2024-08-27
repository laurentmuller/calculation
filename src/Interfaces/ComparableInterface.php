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

namespace App\Interfaces;

/**
 * Class implementing this interface allows comparison.
 *
 * @template TComparable of ComparableInterface
 */
interface ComparableInterface
{
    /**
     * Compare this instance with the given other.
     *
     * @param ComparableInterface $other the other instance to compare with
     *
     * @return int 0 if this instance is equal to the other instance, -1 if this instance is less than the other
     *             instance and 1 if this instance is greater than the other instance
     *
     * @psalm-param TComparable $other
     *
     * @psalm-return int<-1, 1>
     */
    public function compare(self $other): int;
}
