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

use App\Interfaces\EnumConstantsInterface;
use App\Interfaces\EnumSortableInterface;
use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\Attribute\ReadableEnum;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumInterface;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumTrait;
use Elao\Enum\FlagBag;

/**
 * Entity permission enumeration.
 *
 * @implements EnumConstantsInterface<string>
 * @implements EnumSortableInterface<EntityPermission>
 */
#[ReadableEnum(prefix: 'rights.')]
enum EntityPermission: int implements EnumConstantsInterface, EnumSortableInterface, TranslatableEnumInterface
{
    use TranslatableEnumTrait;

    /**
     * Allow to add an entity.
     */
    #[EnumCase('add')]
    case ADD = 1 << 0;

    /**
     * Allow to delete an entity.
     */
    #[EnumCase('delete')]
    case DELETE = 1 << 1;

    /**
     * Allow to edit an entity.
     */
    #[EnumCase('edit')]
    case EDIT = 1 << 2;

    /**
     * Allow to export entities.
     */
    #[EnumCase('export')]
    case EXPORT = 1 << 3;

    /**
     * Allow to list entities.
     */
    #[EnumCase('list')]
    case LIST = 1 << 4;

    /**
     * Allow to show an entity.
     */
    #[EnumCase('show')]
    case SHOW = 1 << 5;

    /**
     * Gets this enumeration as constants.
     *
     * @return array<string, string>
     */
    public static function constants(): array
    {
        return \array_reduce(
            self::cases(),
            /** @psalm-param array<string, string> $choices */
            static fn (array $choices, self $type): array => $choices + ['PERMISSION_' . $type->name => $type->name],
            [],
        );
    }

    /**
     * Gets a flag bag with all permission.
     *
     * @return FlagBag<EntityPermission>
     */
    public static function getAllPermission(): FlagBag
    {
        return FlagBag::from(...EntityPermission::sorted());
    }

    /**
     * Gets a flag bag with default permission.
     *
     * @return FlagBag<EntityPermission>
     */
    public static function getDefaultPermission(): FlagBag
    {
        return FlagBag::from(
            EntityPermission::LIST,
            EntityPermission::EXPORT,
            EntityPermission::SHOW
        );
    }

    /**
     * Gets a flag bag with no permission.
     *
     * @psalm-return FlagBag<EntityPermission>
     */
    public static function getNonePermission(): FlagBag
    {
        return new FlagBag(EntityPermission::class);
    }

    /**
     * @return EntityPermission[]
     */
    public static function sorted(): array
    {
        return [
            self::LIST,
            self::SHOW,
            self::ADD,
            self::EDIT,
            self::DELETE,
            self::EXPORT,
        ];
    }

    /**
     * Find an entity permission for the given name, ignoring case consideration.
     */
    public static function tryFromName(string $name): ?self
    {
        foreach (self::cases() as $permission) {
            if ($permission->match($name)) {
                return $permission;
            }
        }

        return null;
    }

    private function match(string $name): bool
    {
        return 0 === \strcasecmp($name, $this->name);
    }
}
