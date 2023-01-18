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
use App\Util\RoleBuilder;
use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\Attribute\ReadableEnum;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

/**
 * Entity permission enumeration.
 *
 * @implements SortableEnumInterface<EntityPermission>
 */
#[ReadableEnum(prefix: 'rights.')]
enum EntityPermission: int implements ReadableEnumInterface, SortableEnumInterface
{
    use ReadableEnumTrait;

    /*
     * Allow to add an entity.
     */
    #[EnumCase('add')]
    case ADD = 1 << 0;
    /*
     * Allow to delete an entity.
     */
    #[EnumCase('delete')]
    case DELETE = 1 << 1;
    /*
     * Allow to edit an entity.
     */
    #[EnumCase('edit')]
    case EDIT = 1 << 2;
    /*
     * Allow to export entities.
     */
    #[EnumCase('export')]
    case EXPORT = 1 << 3;
    /*
     * Allow to list entities.
     */
    #[EnumCase('list')]
    case LIST = 1 << 4;
    /*
     * Allow to show an entity.
     */
    #[EnumCase('show')]
    case SHOW = 1 << 5;

    /**
     * Gets this enumeration as constant.
     *
     * @return array<string, string>
     */
    public static function constants(): array
    {
        $permissions = EntityPermission::cases();

        return \array_combine(
            \array_map(static fn (EntityPermission $e) => 'ATTRIBUTE_' . $e->name, $permissions),
            \array_map(static fn (EntityPermission $e) => $e->name, $permissions)
        );
    }

    /**
     * @return EntityPermission[]
     */
    public static function sorted(): array
    {
        return [
            EntityPermission::LIST,
            EntityPermission::SHOW,
            EntityPermission::ADD,
            EntityPermission::EDIT,
            EntityPermission::DELETE,
            EntityPermission::EXPORT,
        ];
    }

    /**
     * Find an entity permission value from the given name.
     */
    public static function tryFindValue(string $name, int $default = RoleBuilder::INVALID_VALUE): int
    {
        return EntityPermission::tryFromName($name)?->value ?: $default;
    }

    /**
     * Find an entity permission from the given name.
     */
    public static function tryFromName(string $name): ?EntityPermission
    {
        foreach (EntityPermission::cases() as $permission) {
            if (0 === \strcasecmp($name, $permission->name)) {
                return $permission;
            }
        }

        return null;
    }
}
