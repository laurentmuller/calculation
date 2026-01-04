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

namespace App\Model;

use App\Interfaces\ComparableInterface;

/**
 * @template TComparable of ComparableInterface
 *
 * @implements ComparableInterface<TComparable>
 */
abstract class AbstractLogCounter implements \Countable, \Stringable, ComparableInterface
{
    /** @phpstan-var non-negative-int */
    private int $count = 0;

    #[\Override]
    public function count(): int
    {
        return $this->count;
    }

    /**
     * @phpstan-param positive-int $value
     */
    public function increment(int $value = 1): static
    {
        $this->count += $value;

        return $this;
    }
}
