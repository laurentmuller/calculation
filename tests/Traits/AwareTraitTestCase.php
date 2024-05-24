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

namespace App\Tests\Traits;

use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

abstract class AwareTraitTestCase extends KernelTestCase implements ServiceSubscriberInterface
{
    protected ContainerInterface $container;

    protected function setUp(): void
    {
        $this->container = $this->getContainer();
    }

    public static function getSubscribedServices(): array
    {
        return [];
    }

    /**
     * @template T
     *
     * @psalm-param class-string<T> $id
     *
     * @psalm-return T
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected function getService(string $id): mixed
    {
        /** @psalm-var T */
        return $this->container->get($id);
    }
}
