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
 * Attribute to define the sort order of an object.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class SortableEntity
{
    /**
     * Constructor.
     *
     * @param string $name  the property name
     * @param string $order the sort order
     *
     * @psalm-param SortModeInterface::* $order
     */
    public function __construct(public readonly string $name, public readonly string $order = SortModeInterface::SORT_ASC)
    {
    }

    /**
     * Gets the default order of the given object or class.
     *
     * @param object|string $objectOrClass either a string containing the name of
     *                                     the class to reflect, or an object
     * @param bool          $validate      true to validate that the property name exist
     *
     * @return array<string, string> an array with the field as key and the order as value. An
     *                               empty array is returned if not attribute is found.
     *
     * @psalm-template T of object
     *
     * @psalm-param class-string<T>|T $objectOrClass
     *
     * @throws \ReflectionException if the class does not exist or if the validate parameter
     *                              is true and a property name is not found
     */
    public static function getOrder(object|string $objectOrClass, bool $validate = false): array
    {
        $result = [];
        $class = new \ReflectionClass($objectOrClass);
        $attributes = $class->getAttributes(self::class);
        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            $name = $instance->name;
            if ($validate && !$class->hasProperty($name)) {
                throw new \ReflectionException(\sprintf('The property "%s" is not defined in "%s".', $name, $class->getName()));
            }
            $result[$name] = $instance->order;
        }

        return $result;
    }
}