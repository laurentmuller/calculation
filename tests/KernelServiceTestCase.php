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

use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Extends the kernel test case to get services from the container.
 */
abstract class KernelServiceTestCase extends KernelTestCase implements ServiceSubscriberInterface
{
    use ContainerServiceTrait;

    protected ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = static::getContainer();
    }

    public static function getSubscribedServices(): array
    {
        return [];
    }
}
