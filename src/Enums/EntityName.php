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
use App\Traits\EnumExtrasTrait;
use App\Traits\EnumTranslatableTrait;
use App\Utils\StringUtils;
use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\Attribute\ReadableEnum;

use function Symfony\Component\String\u;

/**
 * The entity name enumeration.
 *
 * @implements EnumSortableInterface<EntityName>
 */
#[ReadableEnum(suffix: '.name')]
enum EntityName: string implements EnumConstantsInterface, EnumSortableInterface, EnumTranslatableInterface
{
    use EnumExtrasTrait;
    use EnumTranslatableTrait;

    /*
     * The calculation rights.
     */
    #[EnumCase('calculation', ['offset' => 0])]
    case CALCULATION = 'EntityCalculation';

    /*
     * The calculation state rights.
     */
    #[EnumCase('calculationstate', ['offset' => 1])]
    case CALCULATION_STATE = 'EntityCalculationState';

    /*
     * The category rights.
     */
    #[EnumCase('category', ['offset' => 2])]
    case CATEGORY = 'EntityCategory';

    /*
     * The customer rights.
     */
    #[EnumCase('customer', ['offset' => 3])]
    case CUSTOMER = 'EntityCustomer';

    /*
     * The global margin rights.
     */
    #[EnumCase('globalmargin', ['offset' => 4])]
    case GLOBAL_MARGIN = 'EntityGlobalMargin';

    /*
     * The group rights.
     */
    #[EnumCase('group', ['offset' => 5])]
    case GROUP = 'EntityGroup';

    /*
     * The log rights.
     */
    #[EnumCase('log', ['offset' => 6])]
    case LOG = 'EntityLog';

    /*
     * The product rights.
     */
    #[EnumCase('product', ['offset' => 7])]
    case PRODUCT = 'EntityProduct';

    /*
     * The task rights.
     */
    #[EnumCase('task', ['offset' => 8])]
    case TASK = 'EntityTask';

    /*
     * The user rights.
     */
    #[EnumCase('user', ['offset' => 9])]
    case USER = 'EntityUser';

    /**
     * The entity prefix.
     */
    private const ENTITY_PREFIX = 'Entity';

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
            static fn (array $choices, self $type) => $choices + ['ENTITY_' . $type->name => $type->value],
            [],
        );

        return $result;
    }

    /**
     * Returns if the given value is equal to this value, ignoring case consideration.
     */
    public function matchValue(string $value): bool
    {
        return StringUtils::equalIgnoreCase($value, $this->value);
    }

    /**
     * Gets the offset.
     */
    public function offset(): int
    {
        return $this->getExtraInt('offset');
    }

    /**
     * @return EntityName[]
     */
    public static function sorted(): array
    {
        return [
            EntityName::CALCULATION,
            EntityName::PRODUCT,
            EntityName::TASK,
            EntityName::CATEGORY,
            EntityName::GROUP,
            EntityName::CALCULATION_STATE,
            EntityName::GLOBAL_MARGIN,
            EntityName::USER,
            EntityName::CUSTOMER,
            EntityName::LOG,
        ];
    }

    /**
     * Find an entity name offset for the given subject.
     */
    public static function tryFindOffset(mixed $subject, int $default = RoleInterface::INVALID_VALUE): int
    {
        return EntityName::tryFromMixed($subject)?->offset() ?? $default;
    }

    /**
     * Find an entity name value for the given subject.
     *
     * @psalm-return ($default is null ? (string|null) : string)
     */
    public static function tryFindValue(mixed $subject, string $default = null): ?string
    {
        return EntityName::tryFromMixed($subject)?->value ?: $default;
    }

    /**
     * Find an entity name from the given subject.
     */
    public static function tryFromMixed(mixed $subject): ?EntityName
    {
        if ($subject instanceof EntityName) {
            return $subject;
        } elseif (\is_scalar($subject)) {
            $name = (string) $subject;
        } elseif (\is_object($subject)) {
            $name = $subject::class;
        } else {
            return null;
        }
        $name = u($name)
            ->afterLast('\\')
            ->title()
            ->ensureStart(self::ENTITY_PREFIX)
            ->toString();

        return EntityName::tryFrom($name);
    }
}
