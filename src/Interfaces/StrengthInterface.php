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
 * Interface for strength password.
 *
 * @author Laurent Muller
 */
interface StrengthInterface
{
    /**
     * The maximum strength level.
     */
    public const LEVEL_MAX = 4;

    /**
     * The minimum strength level.
     */
    public const LEVEL_MIN = 0;

    /**
     * The strength level indicating no validation.
     */
    public const LEVEL_NONE = -1;

    /**
     * The map between strength value and strengh text.
     */
    public const LEVEL_TO_LABEL = [
        -1 => 'password.strength_level.none',
        0 => 'password.strength_level.very_weak',
        1 => 'password.strength_level.weak',
        2 => 'password.strength_level.medium',
        3 => 'password.strength_level.strong',
        4 => 'password.strength_level.very_strong',
    ];
}
