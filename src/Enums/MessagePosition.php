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

use App\Interfaces\SortableEnumInterface;
use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

/**
 * The message position for flash bag.
 *
 * @implements SortableEnumInterface<MessagePosition>
 */
enum MessagePosition: string implements ReadableEnumInterface, SortableEnumInterface
{
    use ReadableEnumTrait;

    #[EnumCase('parameters.message_position.bottom-center')]
    case BOTTOM_CENTER = 'bottom-center';
    #[EnumCase('parameters.message_position.bottom-left')]
    case BOTTOM_LEFT = 'bottom-left';
    #[EnumCase('parameters.message_position.bottom-right')]
    case BOTTOM_RIGHT = 'bottom-right';
    #[EnumCase('parameters.message_position.center-center')]
    case CENTER_CENTER = 'center-center';
    #[EnumCase('parameters.message_position.center-left')]
    case CENTER_LEFT = 'center-left';
    #[EnumCase('parameters.message_position.center-right')]
    case CENTER_RIGHT = 'center-right';
    #[EnumCase('parameters.message_position.top-center')]
    case TOP_CENTER = 'top-center';
    #[EnumCase('parameters.message_position.top-left')]
    case TOP_LEFT = 'top-left';
    #[EnumCase('parameters.message_position.top-right')]
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
