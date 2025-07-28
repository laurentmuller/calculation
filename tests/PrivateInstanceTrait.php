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

namespace App\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Trait to test a private constructor.
 *
 * @phpstan-require-extends TestCase
 */
trait PrivateInstanceTrait
{
    /**
     * @param class-string $class
     *
     * @throws \ReflectionException
     */
    public static function assertPrivateInstance(string $class): void
    {
        $reflectionClass = new \ReflectionClass($class);
        $constructor = $reflectionClass->getConstructor();
        self::assertNotNull($constructor);
        $object = $reflectionClass->newInstanceWithoutConstructor();
        $constructor->invoke($object);
        self::assertInstanceOf($class, $object);
    }
}
