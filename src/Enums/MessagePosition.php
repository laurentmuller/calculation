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
 * The message position for flash bag.
 *
 * @implements SortableEnumInterface<MessagePosition>
 */
enum MessagePosition: string implements DefaultEnumInterface, ReadableEnumInterface, SortableEnumInterface
{
    use DefaultEnumTrait;
    use ReadableEnumTrait;

    /*
     * Bottom center position.
     */
    #[EnumCase('message_position.bottom_center')]
    case BOTTOM_CENTER = 'bottom-center';
    /*
     * Bottom left position.
     */
    #[EnumCase('message_position.bottom_left')]
    case BOTTOM_LEFT = 'bottom-left';
    /*
     * Bottom right position.
     */
    #[EnumCase('message_position.bottom_right', ['default' => true])]
    case BOTTOM_RIGHT = 'bottom-right';
    /*
     * Center position.
     */
    #[EnumCase('message_position.center_center')]
    case CENTER_CENTER = 'center-center';
    /*
     * Center left position.
     */
    #[EnumCase('message_position.center_left')]
    case CENTER_LEFT = 'center-left';
    /*
     * Center right position.
     */
    #[EnumCase('message_position.center_right')]
    case CENTER_RIGHT = 'center-right';
    /*
     * Top center position.
     */
    #[EnumCase('message_position.top_center')]
    case TOP_CENTER = 'top-center';

    /*
     * Top left position.
     */
    #[EnumCase('message_position.top_left')]
    case TOP_LEFT = 'top-left';

    /*
     * Top right position.
     */
    #[EnumCase('message_position.top_right')]
    case TOP_RIGHT = 'top-right';

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
