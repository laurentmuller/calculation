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
use App\Util\Utils;
use Elao\Enum\Attribute\EnumCase;
use Elao\Enum\ExtrasTrait;
use Elao\Enum\ReadableEnumInterface;
use Elao\Enum\ReadableEnumTrait;

/**
 * The entity name enumeration.
 *
 *  @implements SortableEnumInterface<EntityName>
 */
enum EntityName: string implements ReadableEnumInterface, SortableEnumInterface
{
    use ExtrasTrait;
    use ReadableEnumTrait;

    /*
     * The calculation rights.
     */
    #[EnumCase('calculation.name', ['offset' => 0])]
    case CALCULATION = 'EntityCalculation';
    /*
     * The calculation state rights.
     */
    #[EnumCase('calculationstate.name', ['offset' => 1])]
    case CALCULATION_STATE = 'EntityCalculationState';
    /*
     * The category rights.
     */
    #[EnumCase('category.name', ['offset' => 2])]
    case CATEGORY = 'EntityCategory';
    /*
     * The customer rights.
     */
    #[EnumCase('customer.name', ['offset' => 3])]
    case CUSTOMER = 'EntityCustomer';
    /*
     * The global margin rights.
     */
    #[EnumCase('globalmargin.name', ['offset' => 4])]
    case GLOBAL_MARGIN = 'EntityGlobalMargin';
    /*
     * The group rights.
     */
    #[EnumCase('group.name', ['offset' => 5])]
    case GROUP = 'EntityGroup';
    /*
     * The log rights.
     */
    #[EnumCase('log.name', ['offset' => 6])]
    case LOG = 'EntityLog';
    /*
     * The product rights.
     */
    #[EnumCase('product.name', ['offset' => 7])]
    case PRODUCT = 'EntityProduct';
    /*
     * The task rights.
     */
    #[EnumCase('task.name', ['offset' => 8])]
    case TASK = 'EntityTask';
    /*
     * The user rights.
     */
    #[EnumCase('user.name', ['offset' => 9])]
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
        $result = [];
        $entities = EntityName::cases();
        foreach ($entities as $entity) {
            $result['ENTITY_' . $entity->name] = $entity->value;
        }

        return $result;
    }

    /**
     * Returns if the given value is equal to this value.
     */
    public function match(string $value): bool
    {
        return $this->value === $value;
    }

    /**
     * Gets the offset.
     */
    public function offset(): int
    {
        return (int) $this->getExtra('offset');
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
     * Find an entity name from the given subject.
     */
    public static function tryFromMixed(mixed $subject): ?EntityName
    {
        if (\is_string($subject)) {
            $name = $subject;
        } elseif (\is_object($subject)) {
            $name = $subject::class;
        } elseif (\is_scalar($subject)) {
            $name = (string) $subject;
        } else {
            return null;
        }
        if (false !== ($pos = \strrpos($name, '\\'))) {
            $name = \substr($name, $pos + 1);
        }
        if (!Utils::startWith($name, self::ENTITY_PREFIX)) {
            $name = self::ENTITY_PREFIX . $name;
        }

        return EntityName::tryFrom($name);
    }
}
