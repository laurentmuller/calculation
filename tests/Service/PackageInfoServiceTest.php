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
use App\Tests\KernelServiceTestCase;

final class PackageInfoServiceTest extends KernelServiceTestCase
{
    private PackageInfoService $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->getService(PackageInfoService::class);
    }

    public function testGetDebugPackages(): void
    {
        $actual = $this->service->getDebugPackages();
        self::assertNotEmpty($actual);
    }

    public function testGetPackage(): void
    {
        $actual = $this->service->getPackage('symfony/mime');
        self::assertIsArray($actual);

        $actual = $this->service->getPackage('fake/fake');
        self::assertNull($actual);
    }

    public function testGetPackages(): void
    {
        $actual = $this->service->getPackages();
        self::assertNotEmpty($actual);
    }

    public function testGetRuntimePackages(): void
    {
        $actual = $this->service->getRuntimePackages();
        self::assertNotEmpty($actual);
    }
}
