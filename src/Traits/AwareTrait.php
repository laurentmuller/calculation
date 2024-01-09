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

/**
 * Trait to get service from the container.
 *
 * @property \Psr\Container\ContainerInterface $container
 */
trait AwareTrait
{
    /**
     * @template T
     *
     * @param class-string<T> $class
     *
     * @return T
     *
     * @throws \LogicException if the service can not be found
     */
    protected function getContainerService(string $function, string $class): mixed
    {
        $id = self::class . '::' . $function;
        if (!$this->container->has($id)) {
            throw new \LogicException($this->getErrorMessage($class, $id));
        }

        try {
            return $this->container->get($id);
        } catch (ContainerExceptionInterface $e) {
            throw new \LogicException($this->getErrorMessage($class, $id), $e->getCode(), $e);
        }
    }

    private function getErrorMessage(string $class, string $id): string
    {
        return \sprintf('Unable to find service "%s" from "%s".', $class, $id);
    }
}
