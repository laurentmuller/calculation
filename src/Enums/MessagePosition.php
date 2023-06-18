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
 * The message position for flash bag.
 *
 * @implements EnumDefaultInterface<MessagePosition>
 * @implements EnumSortableInterface<MessagePosition>
 */
#[ReadableEnum(prefix: 'message_position.', useValueAsDefault: true)]
enum MessagePosition: string implements EnumDefaultInterface, EnumSortableInterface, EnumTranslatableInterface
{
    use EnumDefaultTrait;
    use EnumTranslatableTrait;

    /*
     * Bottom center position.
     */
    case BOTTOM_CENTER = 'bottom-center';

    /*
     * Bottom left position.
     */
    case BOTTOM_LEFT = 'bottom-left';

    /*
     * Bottom right position.
     */
    #[EnumCase(extras: [EnumDefaultInterface::NAME => true])]
    case BOTTOM_RIGHT = 'bottom-right';

    /*
     * Center position.
     */
    case CENTER_CENTER = 'center-center';

    /*
     * Center left position.
     */
    case CENTER_LEFT = 'center-left';

    /*
     * Center right position.
     */
    case CENTER_RIGHT = 'center-right';

    /*
     * Top center position.
     */
    case TOP_CENTER = 'top-center';

    /*
     * Top left position.
     */
    case TOP_LEFT = 'top-left';

    /*
     * Top right position.
     */
    case TOP_RIGHT = 'top-right';

    public function getAngle(): int
    {
        return match ($this) {
            MessagePosition::TOP_LEFT => 315,
            MessagePosition::TOP_CENTER,
            MessagePosition::CENTER_CENTER => 0,
            MessagePosition::TOP_RIGHT => 45,

            MessagePosition::CENTER_LEFT => 270,
            MessagePosition::CENTER_RIGHT => 90,

            MessagePosition::BOTTOM_LEFT => 225,
            MessagePosition::BOTTOM_CENTER => 180,
            MessagePosition::BOTTOM_RIGHT => 135,
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            MessagePosition::CENTER_CENTER => 'fa-solid fa-arrows-up-down-left-right',
            default => 'fa-solid fa-arrow-up fa-rotate-by'
        };
    }

    /**
     * @return MessagePosition[]
     */
    public static function sorted(): array
    {
        return [
            MessagePosition::TOP_LEFT,
            MessagePosition::TOP_CENTER,
            MessagePosition::TOP_RIGHT,

            MessagePosition::CENTER_LEFT,
            MessagePosition::CENTER_CENTER,
            MessagePosition::CENTER_RIGHT,

            MessagePosition::BOTTOM_LEFT,
            MessagePosition::BOTTOM_CENTER,
            MessagePosition::BOTTOM_RIGHT,
        ];
    }
}
