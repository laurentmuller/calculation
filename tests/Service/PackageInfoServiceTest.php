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

use App\Service\PackageInfoService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class PackageInfoServiceTest extends TestCase
{
    private PackageInfoService $service;

    #[\Override]
    protected function setUp(): void
    {
        $this->service = $this->createService();
    }

    public function testCount(): void
    {
        $actual = \count($this->service);
        self::assertSame(2, $actual);
    }

    public function testGetDebugPackages(): void
    {
        $actual = $this->service->getDebugPackages();
        self::assertCount(1, $actual);
    }

    public function testGetPackageFound(): void
    {
        $actual = $this->service->getPackage('symfony/finder');
        self::assertSame('symfony/finder', $actual['name']);
    }

    public function testGetPackageNotFound(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Unknown package name: "fake/fake".');
        $this->service->getPackage('fake/fake');
    }

    public function testGetPackages(): void
    {
        $actual = $this->service->getPackages();
        self::assertCount(2, $actual);
    }

    public function testGetRuntimePackages(): void
    {
        $actual = $this->service->getRuntimePackages();
        self::assertCount(1, $actual);
    }

    public function testHasPackage(): void
    {
        self::assertTrue($this->service->hasPackage('symfony/finder'));
        self::assertFalse($this->service->hasPackage('fake/fake'));
    }

    public function testLicenseFound(): void
    {
        $package = $this->service->getPackage('symfony/finder');
        self::assertNotNull($package['license']);
    }

    private function createService(): PackageInfoService
    {
        $jsonPath = __DIR__ . '/../files/json/package.json';
        $vendorPath = __DIR__ . '/../../vendor';
        $cache = new ArrayAdapter();

        return new PackageInfoService($jsonPath, $vendorPath, $cache);
    }
}
