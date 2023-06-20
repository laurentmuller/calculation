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
use App\Interfaces\EnumTranslatableInterface;
use App\Interfaces\RoleInterface;
use App\Traits\EnumTranslatableTrait;
use App\Utils\StringUtils;
use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\Attribute\ReadableEnum;

/**
 * Entity permission enumeration.
 *
 * @implements EnumSortableInterface<EntityPermission>
 */
#[ReadableEnum(prefix: 'rights.')]
enum EntityPermission: int implements EnumConstantsInterface, EnumSortableInterface, EnumTranslatableInterface
{
    use EnumTranslatableTrait;

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
        /** @psalm-var array<string, string> $result */
        $result = \array_reduce(
            self::cases(),
            static fn (array $choices, self $type) => $choices + ['ATTRIBUTE_' . $type->name => $type->name],
            [],
        );

        return $result;
    }

    /**
     * Returns if the given name is equal to this name, ignoring case consideration.
     */
    public function matchName(string $name): bool
    {
        return StringUtils::equalIgnoreCase($name, $this->name);
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
     * Find an entity permission value from the given name, ignoring case consideration.
     */
    public static function tryFindValue(string $name, int $default = RoleInterface::INVALID_VALUE): int
    {
        return self::tryFromName($name)?->value ?: $default;
    }

    /**
     * Find an entity permission from the given name, ignoring case consideration.
     *
     * @see EntityPermission::matchName()
     */
    public static function tryFromName(string $name): ?self
    {
        foreach (self::cases() as $permission) {
            if ($permission->matchName($name)) {
                return $permission;
            }
        }

        return null;
    }
}
