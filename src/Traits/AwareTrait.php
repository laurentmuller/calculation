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

/**
 * Trait to get service from container.
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
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected function getServiceFromContainer(string $class, string $function): mixed
    {
        $id = self::class . '::' . $function;
        if (!$this->container->has($id)) {
            throw new \LogicException(\sprintf('Unable to find service "%s" from "%s".', $class, $id));
        }

        return $this->container->get($id);
    }
}
