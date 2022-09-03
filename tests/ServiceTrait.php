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

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Trait to get service from container.
 */
trait ServiceTrait
{
    /**
     * Gets the service for the given class name.
     *
     * @template T
     *
     * @psalm-param class-string<T> $class
     *
     * @return T
     */
    protected function getService(string $class)
    {
        /** @var ContainerInterface $container */
        $container = static::getContainer();

        /** @var T $service */
        $service = $container->get($class);

        return $service;
    }
}
