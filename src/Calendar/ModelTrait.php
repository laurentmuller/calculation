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

namespace App\Calendar;

/**
 * Trait to check for model class name.
 */
trait ModelTrait
{
    /**
     * Checks if the given class name exist.
     *
     * @param string|null $className    the class name to verify
     * @param string      $defaultClass the default class name to use if the class name si null
     *
     * @return string the class name if no exception
     *
     * @throws CalendarException if the given class name does not exist
     *
     * @template T
     * @psalm-param class-string<T>|null $className
     * @psalm-param class-string<T> $defaultClass
     * @psalm-return  class-string<T>
     */
    protected function checkClass(?string $className, string $defaultClass): string
    {
        $name = $className ?: $defaultClass;
        if (!\class_exists($name)) {
            throw new CalendarException("Class '$name' not found.");
        }

        return $name;
    }
}
