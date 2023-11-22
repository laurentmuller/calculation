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
 * Interface to get the constant enumerations.
 *
 * @template TValue as (string|int)
 */
interface EnumConstantsInterface
{
    /**
     * Gets the constant enumerations.
     *
     * @return array<string, TValue>
     */
    public static function constants(): array;
}
