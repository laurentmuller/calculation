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

use App\Interfaces\EnumDefaultInterface;
use App\Interfaces\EnumSortableInterface;
use App\Interfaces\EnumTranslatableInterface;
use App\Traits\EnumDefaultTrait;
use App\Traits\EnumTranslatableTrait;
use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\Attribute\ReadableEnum;

/**
 * The password strength level.
 *
 * @implements EnumDefaultInterface<StrengthLevel>
 * @implements EnumSortableInterface<StrengthLevel>
 */
#[ReadableEnum(prefix: 'strength_level.')]
enum StrengthLevel: int implements EnumDefaultInterface, EnumSortableInterface, EnumTranslatableInterface
{
    use EnumDefaultTrait;
    use EnumTranslatableTrait;

    /*
     * Medium level.
     */
    #[EnumCase('medium')]
    case MEDIUM = 2;

    /*
     * No validation level (default value).
     */
    #[EnumCase('none', [EnumDefaultInterface::NAME => true])]
    case NONE = -1;

    /*
     * Strong level.
     */
    #[EnumCase('strong')]
    case STRONG = 3;

    /*
     * Very strong level.
     */
    #[EnumCase('very_strong')]
    case VERY_STRONG = 4;

    /*
     * Very weak level.
     */
    #[EnumCase('very_weak')]
    case VERY_WEAK = 0;

    /*
     * Weak level.
     */
    #[EnumCase('weak')]
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
     * Returns the percentage of this level.
     *
     * @return int a value between 0 and  100
     */
    public function percent(): int
    {
        return \max(0, ($this->value + 1) * 20);
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
        return \array_map(static fn (StrengthLevel $level): int => $level->value, StrengthLevel::sorted());
    }
}
