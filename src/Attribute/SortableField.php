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

use App\Enums\SortMode;

/**
 * Attribute to define the sort order of a property.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
readonly class SortableField
{
    /**
     * @param SortMode $direction the sort direction
     */
    public function __construct(public SortMode $direction = SortMode::ASC)
    {
    }

    /**
     * Gets the default order of the given object or class for the given field.
     *
     * @template T of object
     *
     * @param T|class-string<T> $objectOrClass either a string containing the name of the class to reflect, or an object
     * @param string            $name          the property name to get order for
     *
     * @return ?SortMode the default sort direction or null if no attribute is found
     *
     * @throws \ReflectionException if the class does not exist
     */
    public static function getDirection(object|string $objectOrClass, string $name): ?SortMode
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

        return $attributes[0]->newInstance()->direction;
    }
}
