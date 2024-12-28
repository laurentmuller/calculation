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

namespace App\Traits;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

/**
 * Trait to get service from the container.
 *
 * @property ContainerInterface $container
 */
trait AwareTrait
{
    /**
     * Gets a service from this container.
     *
     * @template T
     *
     * @param string          $function the calling function name
     * @param class-string<T> $class    the service class name to get for
     *
     * @return T the service
     *
     * @throws \LogicException if the service cannot be found
     */
    protected function getContainerService(string $function, string $class): mixed
    {
        $id = \sprintf('%s::%s', self::class, $function);
        if (!$this->container->has($id)) {
            throw $this->createLogicException($class, $id);
        }

        try {
            /** @psalm-var T */
            return $this->container->get($id);
        } catch (ContainerExceptionInterface $e) {
            throw $this->createLogicException($class, $id, $e);
        }
    }

    private function createLogicException(
        string $class,
        string $id,
        ?ContainerExceptionInterface $previous = null
    ): \LogicException {
        $message = \sprintf('Unable to find service "%s" from "%s".', $class, $id);
        $code = $previous instanceof \Throwable ? $previous->getCode() : 0;

        return new \LogicException($message, $code, $previous);
    }
}
