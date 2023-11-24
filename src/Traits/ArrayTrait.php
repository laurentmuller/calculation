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

/**
 * Trait for array functions.
 */
trait ArrayTrait
{
    /**
     * Gets the values from a single column in the input array.
     */
    public function getColumn(array $values, string|int $key): array
    {
        return \array_column($values, $key);
    }

    /**
     * Gets the filtered values of the given column.
     *
     * @psalm-param int<0,2> $mode
     */
    public function getColumnFilter(array $values, string|int $key, callable $callback = null, int $mode = 0): array
    {
        return \array_filter($this->getColumn($values, $key), $callback, $mode);
    }

    /**
     * Gets the maximum of the given column.
     *
     * @psalm-suppress ArgumentTypeCoercion
     */
    public function getColumnMax(array $values, string|int $key, float $default = 0.0): float
    {
        return [] === $values ? $default : (float) \max($this->getColumn($values, $key));
    }

    /**
     * Gets the sum of the given column.
     */
    public function getColumnSum(array $values, string|int $key, float $default = 0.0): float
    {
        return [] === $values ? $default : (float) \array_sum($this->getColumn($values, $key));
    }
}
