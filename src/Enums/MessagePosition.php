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
use Elao\Enum\Attribute\ReadableEnum;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

/**
 * The message position for flash bag.
 *
 * @implements SortableEnumInterface<MessagePosition>
 */
#[ReadableEnum(prefix: 'message_position.', useValueAsDefault: true)]
enum MessagePosition: string implements DefaultEnumInterface, ReadableEnumInterface, SortableEnumInterface
{
    use DefaultEnumTrait;
    use ReadableEnumTrait;

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
    #[EnumCase(extras: ['default' => true])]
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
