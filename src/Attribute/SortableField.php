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
readonly class SortableField
{
    /**
     * @param string $order the field order
     *
     * @psalm-param SortModeInterface::* $order
     */
    public function __construct(public string $order = SortModeInterface::SORT_ASC)
    {
    }

    /**
     * Gets the default order of the given object or class for the given field.
     *
     * @template T of object
     *
     * @param object|string $objectOrClass either a string containing the name of
     *                                     the class to reflect, or an object
     * @param string        $name          the property name to get order for
     *
     * @return ?string the default order or null if no attribute is found
     *
     * @throws \ReflectionException if the class does not exist
     *
     * @psalm-param T|class-string<T> $objectOrClass
     */
    public static function getOrder(object|string $objectOrClass, string $name): ?string
    {
        $class = new \ReflectionClass($objectOrClass);
        if ($class->hasProperty($name)) {
            $property = $class->getProperty($name);
            $attributes = $property->getAttributes(self::class);
            if ([] !== $attributes) {
                $attribute = $attributes[0];
                $instance = $attribute->newInstance();

                return $instance->order;
            }
        }

        return null;
    }
}
