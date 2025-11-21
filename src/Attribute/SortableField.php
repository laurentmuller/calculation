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

namespace App\Attribute;

use App\Interfaces\SortModeInterface;

/**
 * Attribute to define the sort order of a property.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
readonly class SortableField implements SortModeInterface
{
    /**
     * @param string $order the field order
     *
     * @phpstan-param self::SORT_* $order
     */
    public function __construct(public string $order = self::SORT_ASC)
    {
    }

    /**
     * Gets the default order of the given object or class for the given field.
     *
     * @template T of object
     *
     * @param T|class-string<T> $objectOrClass either a string containing the name of
     *                                         the class to reflect, or an object
     * @param string            $name          the property name to get order for
     *
     * @return ?string the default order or null if no attribute is found
     *
     * @throws \ReflectionException if the class does not exist
     */
    public static function getOrder(object|string $objectOrClass, string $name): ?string
    {
        $class = new \ReflectionClass($objectOrClass);
        if (!$class->hasProperty($name)) {
            return null;
        }
        $property = $class->getProperty($name);
        /** @var \ReflectionAttribute<SortableField>[] $attributes */
        $attributes = $property->getAttributes(self::class);
        if ([] === $attributes) {
            return null;
        }

        return $attributes[0]->newInstance()->order;
    }
}
