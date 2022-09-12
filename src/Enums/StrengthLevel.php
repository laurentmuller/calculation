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

namespace App\Enums;

use App\Interfaces\DefaultEnumInterface;
use App\Interfaces\SortableEnumInterface;
use App\Traits\DefaultEnumTrait;
use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

/**
 * The password strength level.
 *
 * @implements SortableEnumInterface<StrengthLevel>
 */
enum StrengthLevel: int implements DefaultEnumInterface, ReadableEnumInterface, SortableEnumInterface
{
    use DefaultEnumTrait;
    use ReadableEnumTrait;

    /*
     * Medium level.
     */
    #[EnumCase('strength_level.medium')]
    case MEDIUM = 2;

    /*
     * No validation level (default value).
     */
    #[EnumCase('strength_level.none', ['default' => true])]
    case NONE = -1;

    /*
     * Strong level.
     */
    #[EnumCase('strength_level.strong')]
    case STRONG = 3;

    /*
     * Very strong level.
     */
    #[EnumCase('strength_level.very_strong')]
    case VERY_STRONG = 4;

    /*
     * Very weak level.
     */
    #[EnumCase('strength_level.very_weak')]
    case VERY_WEAK = 0;

    /*
     * Weak level.
     */
    #[EnumCase('strength_level.weak')]
    case WEAK = 1;

    /**
     * Returns if this value is smaller than the given level.
     */
    public function isSmaller(int|StrengthLevel $level): bool
    {
        if ($level instanceof StrengthLevel) {
            $level = $level->value;
        }

        return $this->value < $level;
    }

    /**
     * @return StrengthLevel[]
     */
    public static function sorted(): array
    {
        return [
            StrengthLevel::NONE,
            StrengthLevel::VERY_WEAK,
            StrengthLevel::WEAK,
            StrengthLevel::MEDIUM,
            StrengthLevel::STRONG,
            StrengthLevel::VERY_STRONG,
        ];
    }

    /**
     * Gets the strength level values.
     *
     * @return int[]
     */
    public static function values(): array
    {
        return \array_map(fn (StrengthLevel $level): int => $level->value, StrengthLevel::sorted());
    }
}
