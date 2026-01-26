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
use App\Traits\EnumExtrasTrait;
use App\Utils\StringUtils;
use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\Attribute\ReadableEnum;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumInterface;
use Elao\Enum\Bridge\Symfony\Translation\TranslatableEnumTrait;

/**
 * The entity name enumeration.
 *
 * @implements EnumSortableInterface<EntityName>
 */
#[ReadableEnum(suffix: '.name')]
enum EntityName: string implements ConstantsInterface, EnumSortableInterface, TranslatableEnumInterface
{
    use EnumExtrasTrait;
    use TranslatableEnumTrait;

    /**
     * The calculation rights.
     */
    #[EnumCase('calculation', ['offset' => 0])]
    case CALCULATION = 'EntityCalculation';

    /**
     * The calculation state rights.
     */
    #[EnumCase('calculationstate', ['offset' => 1])]
    case CALCULATION_STATE = 'EntityCalculationState';

    /**
     * The category rights.
     */
    #[EnumCase('category', ['offset' => 2])]
    case CATEGORY = 'EntityCategory';

    /**
     * The customer rights.
     */
    #[EnumCase('customer', ['offset' => 3])]
    case CUSTOMER = 'EntityCustomer';

    /**
     * The global margin rights.
     */
    #[EnumCase('globalmargin', ['offset' => 4])]
    case GLOBAL_MARGIN = 'EntityGlobalMargin';

    /**
     * The group rights.
     */
    #[EnumCase('group', ['offset' => 5])]
    case GROUP = 'EntityGroup';

    /**
     * The log rights.
     */
    #[EnumCase('log', ['offset' => 6])]
    case LOG = 'EntityLog';

    /**
     * The product rights.
     */
    #[EnumCase('product', ['offset' => 7])]
    case PRODUCT = 'EntityProduct';

    /**
     * The task rights.
     */
    #[EnumCase('task', ['offset' => 8])]
    case TASK = 'EntityTask';

    /**
     * The user rights.
     */
    #[EnumCase('user', ['offset' => 9])]
    case USER = 'EntityUser';

    /**
     * The entity prefix.
     */
    private const string ENTITY_PREFIX = 'Entity';

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
            static fn (array $carry, self $type): array => $carry + ['ENTITY_' . $type->name => $type->value],
            [],
        );
    }

    /**
     * Gets the form field name.
     */
    public function getFormField(): string
    {
        return \substr($this->value, \strlen(self::ENTITY_PREFIX));
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
    #[\Override]
    public static function sorted(): array
    {
        return [
            self::CALCULATION,
            self::PRODUCT,
            self::TASK,
            self::CATEGORY,
            self::GROUP,
            self::CALCULATION_STATE,
            self::GLOBAL_MARGIN,
            self::USER,
            self::CUSTOMER,
            self::LOG,
        ];
    }

    /**
     * Find an entity name from the given subject.
     */
    public static function tryFromMixed(mixed $subject): ?self
    {
        if ($subject instanceof self) {
            return $subject;
        }

        if (\is_scalar($subject)) {
            $name = (string) $subject;
        } elseif (\is_object($subject)) {
            $name = $subject::class;
        } else {
            return null;
        }

        $name = StringUtils::unicode($name)
            ->afterLast('\\')
            ->title()
            ->ensureStart(self::ENTITY_PREFIX)
            ->toString();

        return self::tryFrom($name);
    }
}
