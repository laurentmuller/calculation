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

use App\Enums\Environment;
use App\Service\KernelInfoService;
use App\Service\SymfonyInfoService;
use App\Tests\KernelServiceTestCase;

final class KernelInfoServiceTest extends KernelServiceTestCase
{
    private KernelInfoService $service;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->getService(KernelInfoService::class);
    }

    public function testGetBuildInfo(): void
    {
        $actual = $this->service->getBuildInfo();
        self::assertSame('Build', $actual['name']);
    }

    public function testGetCacheInfo(): void
    {
        $actual = $this->service->getCacheInfo();
        self::assertSame('Cache', $actual['name']);
    }

    public function testGetCharset(): void
    {
        $actual = $this->service->getCharset();
        self::assertSame('UTF-8', $actual);
    }

    public function testGetDebugStatus(): void
    {
        $actual = $this->service->getDebugStatus();
        self::assertSame(SymfonyInfoService::LABEL_DISABLED, $actual);
    }

    public function testGetEnvironment(): void
    {
        $actual = $this->service->getEnvironment();
        self::assertSame(Environment::TEST, $actual);
    }

    public function testGetLogInfo(): void
    {
        $actual = $this->service->getLogInfo();
        self::assertSame('Logs', $actual['name']);
    }

    public function testGetMode(): void
    {
        $actual = $this->service->getMode();
        self::assertSame(Environment::TEST, $actual);
    }

    public function testGetProjectDir(): void
    {
        $actual = $this->service->getProjectDir();
        self::assertStringContainsString('calculation', $actual);
    }

    public function testIsDebug(): void
    {
        $actual = $this->service->isDebug();
        self::assertFalse($actual);
    }
}
