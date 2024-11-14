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

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Trait to get service from the container.
 *
 * @psalm-require-extends KernelTestCase
 */
trait ContainerServiceTrait
{
    /**
     * Gets the service for the given class name.
     *
     * @template T
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    protected function getService(string $class)
    {
        /** @psalm-var T */
        return static::getContainer()->get($class); // @phpstan-ignore varTag.nativeType
    }

    /**
     * Sets a service.
     */
    protected function setService(string $id, object $service): void
    {
        static::getContainer()->set($id, $service);
    }
}
