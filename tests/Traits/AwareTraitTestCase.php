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

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

abstract class AwareTraitTestCase extends TestCase implements ServiceSubscriberInterface
{
    public Stub&ContainerInterface $container;

    #[\Override]
    protected function setUp(): void
    {
        $this->container = self::createStub(ContainerInterface::class);
    }

    public function getContainer(): Stub&ContainerInterface
    {
        return $this->container;
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [];
    }
}
