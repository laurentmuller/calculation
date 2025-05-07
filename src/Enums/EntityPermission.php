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

use App\Interfaces\ConstantsInterface;
use App\Interfaces\EnumSortableInterface;
use App\Utils\StringUtils;
use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\Attribute\ReadableEnum;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumInterface;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumTrait;
use Elao\Enum\FlagBag;

/**
 * Entity permission enumeration.
 *
 * @implements ConstantsInterface<string>
 * @implements EnumSortableInterface<EntityPermission>
 */
#[ReadableEnum(prefix: 'rights.')]
enum EntityPermission: int implements ConstantsInterface, EnumSortableInterface, TranslatableEnumInterface
{
    use TranslatableEnumTrait;

    /**
     * Allows adding an entity.
     */
    #[EnumCase('add')]
    case ADD = 1 << 0;

    /**
     * Allows deleting an entity.
     */
    #[EnumCase('delete')]
    case DELETE = 1 << 1;

    /**
     * Allows editing an entity.
     */
    #[EnumCase('edit')]
    case EDIT = 1 << 2;

    /**
     * Allows exporting entities.
     */
    #[EnumCase('export')]
    case EXPORT = 1 << 3;

    /**
     * Allows listing entities.
     */
    #[EnumCase('list')]
    case LIST = 1 << 4;

    /**
     * Allows showing an entity.
     */
    #[EnumCase('show')]
    case SHOW = 1 << 5;

    /**
     * Gets this enumeration as constants.
     *
     * @return array<string, string>
     */
    #[\Override]
    public static function constants(): array
    {
        return \array_reduce(
            self::cases(),
            /** @phpstan-param array<string, string> $choices */
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
     * @return FlagBag<EntityPermission>
     */
    public static function getNonePermission(): FlagBag
    {
        return new FlagBag(EntityPermission::class);
    }

    /**
     * @return EntityPermission[]
     */
    #[\Override]
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
        return StringUtils::equalIgnoreCase($name, $this->name);
    }
}
