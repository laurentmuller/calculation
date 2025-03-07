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
 * Entity action enumeration.
 *
 * @implements PdfEnumDefaultInterface<EntityAction>
 * @implements EnumSortableInterface<EntityAction>
 */
#[ReadableEnum(prefix: 'entity_action.', useValueAsDefault: true)]
enum EntityAction: string implements EnumSortableInterface, PdfEnumDefaultInterface, TranslatableEnumInterface
{
    use PdfEnumDefaultTrait;
    use TranslatableEnumTrait;

    /**
     * Edit the entity (default value).
     */
    #[EnumCase(extras: [PdfEnumDefaultInterface::NAME => true])]
    case EDIT = 'edit';

    /**
     * No action.
     */
    case NONE = 'none';

    /**
     * Show the entity.
     */
    case SHOW = 'show';

    /**
     * @return EntityAction[]
     */
    #[\Override]
    public static function sorted(): array
    {
        return [
            self::EDIT,
            self::SHOW,
            self::NONE,
        ];
    }

    /**
     * Gets the action values.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return \array_map(fn (self $action): string => $action->value, self::sorted());
    }
}
