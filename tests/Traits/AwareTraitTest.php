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

use App\Traits\AwareTrait;
use Faker\Container\ContainerException;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;

final class AwareTraitTest extends AwareTraitTestCase
{
    use AwareTrait;

    public function testGetWithException(): void
    {
        $code = 200;
        self::expectExceptionCode($code);
        self::expectException(\LogicException::class);
        self::expectExceptionMessageMatches('/Unable to find service.*/');

        /** @var MockObject&ContainerInterface $container */
        $container = $this->container;
        $container->method('has')
            ->willReturn(true);
        $container->method('get')
            ->willThrowException(new ContainerException(code: $code));
        $this->getContainerService(__FUNCTION__, self::class);
    }

    public function testHasWithException(): void
    {
        self::expectExceptionCode(0);
        self::expectException(\LogicException::class);
        self::expectExceptionMessageMatches('/Unable to find service.*/');

        /** @var MockObject&ContainerInterface $container */
        $container = $this->container;
        $container->method('has')
            ->willReturn(false);
        $this->getContainerService(__FUNCTION__, self::class);
    }

    public function testWithoutException(): void
    {
        /** @var MockObject&ContainerInterface $container */
        $container = $this->container;
        $container->method('has')
            ->willReturn(true);
        $container->method('get')
            ->willReturn($this);
        $actual = $this->getContainerService(__FUNCTION__, self::class);
        self::assertSame($this, $actual);
    }
}
