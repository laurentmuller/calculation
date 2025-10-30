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

namespace App\Tests\Service;

use App\Service\BundleInfoService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class BundleInfoServiceTest extends TestCase
{
    public function testGetBundles(): void
    {
        $bundle = $this->createMockBundle();
        $kernel = $this->createMockKernel($bundle);
        $service = new BundleInfoService($kernel, new ArrayAdapter(), __DIR__);
        $actual = $service->getBundles();
        self::assertNotEmpty($actual);
    }

    private function createMockBundle(): BundleInterface
    {
        $bundle = $this->createMock(BundleInterface::class);
        $bundle->expects(self::once())
            ->method('getNamespace')
            ->willReturn('App\Tests\Service');
        $bundle->expects(self::once())
            ->method('getPath')
            ->willReturn(__DIR__);

        return $bundle;
    }

    private function createMockKernel(BundleInterface $bundle): KernelInterface
    {
        $kernel = $this->createMock(KernelInterface::class);
        $kernel->expects(self::once())
            ->method('getBundles')
            ->willReturn(['fakeBundle' => $bundle]);

        return $kernel;
    }
}
