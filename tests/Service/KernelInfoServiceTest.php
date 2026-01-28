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
use App\Utils\FileUtils;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelInterface;

final class KernelInfoServiceTest extends TestCase
{
    private const string CHARSET = 'utf-8';
    private const Environment ENVIRONMENT = Environment::TEST;

    public function testGetBuildInfo(): void
    {
        $service = $this->createService();
        $actual = $service->getBuildInfo();
        self::assertSame('Build', $actual['name']);
    }

    public function testGetCacheInfo(): void
    {
        $service = $this->createService();
        $actual = $service->getCacheInfo();
        self::assertSame('Cache', $actual['name']);
    }

    public function testGetCharset(): void
    {
        $service = $this->createService();
        $actual = $service->getCharset();
        self::assertSame(self::CHARSET, $actual);
    }

    public function testGetDebugStatus(): void
    {
        $service = $this->createService();
        $actual = $service->getDebugStatus();
        self::assertSame(SymfonyInfoService::LABEL_DISABLED, $actual);
    }

    public function testGetEnvironment(): void
    {
        $service = $this->createService();
        $actual = $service->getEnvironment();
        self::assertSame(self::ENVIRONMENT, $actual);
    }

    public function testGetLogInfo(): void
    {
        $service = $this->createService();
        $actual = $service->getLogInfo();
        self::assertSame('Logs', $actual['name']);
    }

    public function testGetMode(): void
    {
        $service = $this->createService();
        $actual = $service->getMode();
        self::assertSame(self::ENVIRONMENT, $actual);
    }

    public function testGetProjectDir(): void
    {
        $service = $this->createService();
        $actual = $service->getProjectDir();
        $expected = FileUtils::normalize(__DIR__);
        self::assertSame($expected, $actual);
    }

    public function testIsDebug(): void
    {
        $service = $this->createService();
        $actual = $service->isDebug();
        self::assertFalse($actual);
    }

    private function createService(): KernelInfoService
    {
        $dir = __DIR__;
        $mode = self::ENVIRONMENT->value;

        $kernel = $this->createMock(KernelInterface::class);
        $kernel->method('getEnvironment')
            ->willReturn($mode);
        $kernel->method('getCharset')
            ->willReturn(self::CHARSET);

        $kernel->method('getBuildDir')
            ->willReturn($dir);
        $kernel->method('getCacheDir')
            ->willReturn($dir);
        $kernel->method('getLogDir')
            ->willReturn($dir);

        return new KernelInfoService($kernel, __DIR__, $mode);
    }
}
