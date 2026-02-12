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

use App\Interfaces\EnumSortableInterface;
use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\Attribute\ReadableEnum;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumInterface;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumTrait;
use fpdf\Interfaces\PdfEnumDefaultInterface;
use fpdf\Traits\PdfEnumDefaultTrait;

/**
 * The message position for flashbag.
 *
 * @implements PdfEnumDefaultInterface<MessagePosition>
 * @implements EnumSortableInterface<MessagePosition>
 */
#[ReadableEnum(prefix: 'message_position.', useValueAsDefault: true)]
enum MessagePosition: string implements EnumSortableInterface, PdfEnumDefaultInterface, TranslatableEnumInterface
{
    use PdfEnumDefaultTrait;
    use TranslatableEnumTrait;

    /** Bottom center position. */
    case BOTTOM_CENTER = 'bottom-center';

    /** Bottom left position. */
    case BOTTOM_LEFT = 'bottom-left';

    /** Bottom right position. */
    #[EnumCase(extras: [PdfEnumDefaultInterface::NAME => true])]
    case BOTTOM_RIGHT = 'bottom-right';

    /** Center position. */
    case CENTER_CENTER = 'center-center';

    /** Center left position. */
    case CENTER_LEFT = 'center-left';

    /** Center right position. */
    case CENTER_RIGHT = 'center-right';

    /** Top center position. */
    case TOP_CENTER = 'top-center';

    /** Top left position. */
    case TOP_LEFT = 'top-left';

    /** Top right position. */
    case TOP_RIGHT = 'top-right';

    /**
     * Gets the rotation angle of the icon.
     */
    public function getAngle(): int
    {
        return match ($this) {
            self::TOP_LEFT => 315,
            self::TOP_CENTER,
            self::CENTER_CENTER => 0,
            self::TOP_RIGHT => 45,

            self::CENTER_LEFT => 270,
            self::CENTER_RIGHT => 90,

            self::BOTTOM_LEFT => 225,
            self::BOTTOM_CENTER => 180,
            self::BOTTOM_RIGHT => 135,
        };
    }

    /**
     * Gets icon.
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::CENTER_CENTER => 'fa-solid fa-location-crosshairs',
            default => 'fa-solid fa-arrow-up fa-rotate-by'
        };
    }

    /**
     * @return MessagePosition[]
     */
    #[\Override]
    public static function sorted(): array
    {
        return [
            self::TOP_LEFT,
            self::TOP_CENTER,
            self::TOP_RIGHT,

            self::CENTER_LEFT,
            self::CENTER_CENTER,
            self::CENTER_RIGHT,

            self::BOTTOM_LEFT,
            self::BOTTOM_CENTER,
            self::BOTTOM_RIGHT,
        ];
    }
}
