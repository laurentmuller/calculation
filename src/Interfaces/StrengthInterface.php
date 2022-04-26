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
 * Interface for strength password.
 */
interface StrengthInterface
{
    /**
     * The allowed level values.
     */
    public const ALLOWED_LEVELS = [
        self::LEVEL_NONE,
        self::LEVEL_VERY_WEEK,
        self::LEVEL_WEEK,
        self::LEVEL_MEDIUM,
        self::LEVEL_STRONG,
        self::LEVEL_VERY_STRONG,
    ];

    /**
     * The medium level.
     */
    public const LEVEL_MEDIUM = 2;

    /**
     * The no validation level.
     *
     * @var int
     */
    public const LEVEL_NONE = -1;

    /**
     * The strong level.
     */
    public const LEVEL_STRONG = 3;

    /**
     * The very stong level.
     */
    public const LEVEL_VERY_STRONG = 4;

    /**
     * The very weak level.
     */
    public const LEVEL_VERY_WEEK = 0;

    /**
     * The weak level.
     */
    public const LEVEL_WEEK = 1;
}
