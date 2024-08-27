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
 * Interface to get the default enumeration value.
 *
 * @template T of \UnitEnum&EnumDefaultInterface
 */
interface EnumDefaultInterface
{
    /**
     * The default attribute name.
     */
    public const NAME = 'default';

    /**
     * Gets the default enumeration.
     *
     * @psalm-return T
     *
     * @throws \LogicException if the default enumeration is not found
     */
    public static function getDefault(): self;

    /**
     * Returns if this enumeration is the default value.
     */
    public function isDefault(): bool;
}
