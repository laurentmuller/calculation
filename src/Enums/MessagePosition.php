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
    case BOTTOM_CENTER = 'bottom_center';
    /*
     * Bottom left position.
     */
    #[EnumCase('message_position.bottom_left')]
    case BOTTOM_LEFT = 'bottom_left';
    /*
     * Bottom right position.
     */
    #[EnumCase('message_position.bottom_right', ['default' => true])]
    case BOTTOM_RIGHT = 'bottom_right';
    /*
     * Center position.
     */
    #[EnumCase('message_position.center_center')]
    case CENTER_CENTER = 'center_center';
    /*
     * Center left position.
     */
    #[EnumCase('message_position.center_left')]
    case CENTER_LEFT = 'center_left';
    /*
     * Center right position.
     */
    #[EnumCase('message_position.center_right')]
    case CENTER_RIGHT = 'center_right';
    #[EnumCase('message_position.top_center')]
    /*
     * Top center position.
     */
    case TOP_CENTER = 'top_center';
    #[EnumCase('message_position.top_left')]
    /*
     * Top left position.
     */
    case TOP_LEFT = 'top_left';
    #[EnumCase('message_position.top_right')]
    /*
     * Top right position.
     */
    case TOP_RIGHT = 'top_right';

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
