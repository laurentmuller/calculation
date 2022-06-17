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
 * Entity action enumeration.
 *
 *  @implements SortableEnumInterface<EntityAction>
 */
enum EntityAction: string implements ReadableEnumInterface, SortableEnumInterface
{
    use ReadableEnumTrait;

    /*
     * Edit the entity.
     */
    #[EnumCase('action.edit')]
    case EDIT = 'edit';
    /*
     * No action.
     */
    #[EnumCase('action.none')]
    case NONE = 'none';
    /*
     * Show the entity.
     */
    #[EnumCase('action.show')]
    case SHOW = 'show';
    /**
     * @return EntityAction[]
     */
    public static function sorted(): array
    {
        return [
            EntityAction::EDIT,
            EntityAction::SHOW,
            EntityAction::NONE,
        ];
    }
}
