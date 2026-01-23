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

use Symfony\Component\Clock\DatePoint;

/**
 * Attribute to define a parameter name and an optional default value.
 *
 * @phpstan-type TValue = scalar|array|\BackedEnum|DatePoint|null
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Parameter
{
    /**
     * @param string $name    the property name
     * @param mixed  $default the default value
     *
     * @phpstan-param TValue $default
     */
    public function __construct(public string $name, public mixed $default = null)
    {
    }

    /**
     * Gets an instance of this attribute from the given reflection property.
     *
     * @param \ReflectionProperty $property the property to get attribute for
     *
     * @return ?Parameter an instance of this attribute, if found; null otherwise
     */
    public static function getAttributeFromProperty(\ReflectionProperty $property): ?self
    {
        /** @var \ReflectionAttribute<Parameter>[] $attributes */
        $attributes = $property->getAttributes(self::class);

        return [] === $attributes ? null : $attributes[0]->newInstance();
    }
}
