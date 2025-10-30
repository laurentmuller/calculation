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
    public function testGetDebugPackages(): void
    {
        $service = $this->createService();
        $actual = $service->getDebugPackages();
        self::assertNotEmpty($actual);
    }

    public function testGetPackage(): void
    {
        $service = $this->createService();
        $actual = $service->getPackage('symfony/mime');
        self::assertIsArray($actual);

        $actual = $service->getPackage('fake/fake');
        self::assertNull($actual);
    }

    public function testGetPackages(): void
    {
        $service = $this->createService();
        $actual = $service->getPackages();
        self::assertNotEmpty($actual);
    }

    public function testGetRuntimePackages(): void
    {
        $service = $this->createService();
        $actual = $service->getRuntimePackages();
        self::assertNotEmpty($actual);
    }

    public function testLicenseFound(): void
    {
        $service = $this->createService();
        $package = $service->getPackage('symfony/mime');
        self::assertIsArray($package);
        self::assertNull($package['license']);
    }

    public function testLicenseNotFound(): void
    {
        $service = $this->createService();
        $package = $service->getPackage('symfony/mime');
        self::assertIsArray($package);
        self::assertNull($package['license']);
    }

    private function createService(): PackageInfoService
    {
        $path = __DIR__ . '/../files/json/';
        $cache = new ArrayAdapter();

        return new PackageInfoService($path, $cache);
    }
}
