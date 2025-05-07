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

/**
 * Attribute to define a parameter name and an optional default value.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Parameter
{
    /**
     * @param string $name    the property name
     * @param mixed  $default the default value
     */
    public function __construct(public string $name, public mixed $default = null)
    {
    }

    /**
     * Gets the attribute instance.
     *
     * @param object|string $objectOrClass either a string containing the name of
     *                                     the class to reflect, or an object
     * @param string        $name          the property name to get an instance for
     *
     * @return self|null the attribute instance, if found; null otherwise
     *
     * @throws \ReflectionException if the class does not exist
     *
     * @phpstan-template T of object
     *
     * @phpstan-param T|class-string<T> $objectOrClass
     */
    public static function getAttributInstance(object|string $objectOrClass, string $name): ?object
    {
        $class = new \ReflectionClass($objectOrClass);
        if (!$class->hasProperty($name)) {
            return null;
        }
        $property = $class->getProperty($name);
        $attributes = $property->getAttributes(self::class);
        if ([] === $attributes) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

    /**
     * Gets the default value of the parameter.
     *
     * @param object|string $objectOrClass either a string containing the name of
     *                                     the class to reflect, or an object
     * @param string        $name          the property name to get the default value for
     *
     * @return mixed the default value
     *
     * @throws \ReflectionException if the class does not exist
     *
     * @phpstan-template T of object
     *
     * @phpstan-param T|class-string<T> $objectOrClass
     */
    public static function getDefaultValue(object|string $objectOrClass, string $name): mixed
    {
        return self::getAttributInstance($objectOrClass, $name)?->default;
    }

    /**
     * Gets the parameter name.
     *
     * @param object|string $objectOrClass either a string containing the name of
     *                                     the class to reflect, or an object
     * @param string        $name          the property name to get the parameter name for
     *
     * @return ?string the parameter name or null if no attribute is found
     *
     * @throws \ReflectionException if the class does not exist
     *
     * @phpstan-template T of object
     *
     * @phpstan-param T|class-string<T> $objectOrClass
     */
    public static function getName(object|string $objectOrClass, string $name): ?string
    {
        return self::getAttributInstance($objectOrClass, $name)?->name;
    }

    /**
     * Returns a value indicating if the given value is the default value.
     *
     * @param object|string $objectOrClass either a string containing the name of
     *                                     the class to reflect, or an object
     * @param string        $name          the property name to get the default value for
     * @param mixed         $value         the value to compare with default
     *
     * @return bool true if default value; false otherwise
     *
     * @throws \ReflectionException if the class does not exist
     *
     * @phpstan-template T of object
     *
     * @phpstan-param T|class-string<T> $objectOrClass
     */
    public static function isDefaultValue(object|string $objectOrClass, string $name, mixed $value = null): bool
    {
        return self::getDefaultValue($objectOrClass, $name) === $value;
    }
}
