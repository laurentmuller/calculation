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

trait ClosureSortTrait
{
    /**
     * Compare the given values using multiple closures.
     *
     * @template TValue
     *
     * @param TValue                        $a           the first value to compare
     * @param TValue                        $b           the second value to compare
     * @param \Closure(TValue, TValue): int ...$closures the closures to use for comparison
     */
    public function compareByClosures(mixed $a, mixed $b, \Closure ...$closures): int
    {
        foreach ($closures as $closure) {
            $result = $closure($a, $b);
            if (0 !== $result) {
                return $result;
            }
        }

        return 0;
    }

    /**
     * Sort the given array using multiple closures and maintaining index association.
     *
     * @template TKey of array-key
     * @template TValue
     *
     * @param array<TKey, TValue>           $array       the array to sort
     * @param \Closure(TValue, TValue): int ...$closures the closures to use for comparison
     */
    public function sortByClosures(array &$array, \Closure ...$closures): bool
    {
        if (\count($array) > 1 && [] !== $closures) {
            return \uasort($array, fn (mixed $a, mixed $b): int => $this->compareByClosures($a, $b, ...$closures));
        }

        return false;
    }

    /**
     * Sort the given array by keys using multiple closures.
     *
     * @template TKey of array-key
     * @template TValue
     *
     * @param array<TKey, TValue>       $array       the array to sort
     * @param \Closure(TKey, TKey): int ...$closures the closures to use for comparison
     */
    public function sortKeysByClosures(array &$array, \Closure ...$closures): bool
    {
        if (\count($array) > 1 && [] !== $closures) {
            return \uksort($array, fn (mixed $a, mixed $b): int => $this->compareByClosures($a, $b, ...$closures));
        }

        return false;
    }
}
