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
use App\Service\SymfonyInfoService;
use App\Tests\KernelServiceTestCase;
use App\Utils\FormatUtils;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpKernel\Kernel;

class SymfonyInfoServiceTest extends KernelServiceTestCase
{
    private SymfonyInfoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->getService(SymfonyInfoService::class);
    }

    public function testGetArchitecture(): void
    {
        $actual = $this->service->getArchitecture();
        self::assertSame('64 bits', $actual);
    }

    public function testGetBuildInfo(): void
    {
        $actual = $this->service->getBuildInfo();
        self::assertStringContainsString('var', $actual);
        self::assertStringContainsString('cache', $actual);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testGetBundles(): void
    {
        $actual = $this->service->getBundles();
        self::assertNotEmpty($actual);
    }

    public function testGetCacheInfo(): void
    {
        $actual = $this->service->getCacheInfo();
        self::assertStringContainsString('var', $actual);
        self::assertStringContainsString('cache', $actual);
    }

    public function testGetCharset(): void
    {
        $actual = $this->service->getCharset();
        self::assertSame('UTF-8', $actual);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testGetDebugPackages(): void
    {
        $actual = $this->service->getDebugPackages();
        self::assertNotEmpty($actual);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testGetDebugRoutes(): void
    {
        $actual = $this->service->getDebugRoutes();
        self::assertEmpty($actual);
    }

    public function testGetEndOfLife(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $actual = $this->service->getEndOfLife();
        $year = \explode('/', Kernel::END_OF_LIFE)[1];
        self::assertStringContainsString($year, $actual);
    }

    public function testGetEndOfMaintenance(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $actual = $this->service->getEndOfMaintenance();
        $year = \explode('/', Kernel::END_OF_MAINTENANCE)[1];
        self::assertStringContainsString($year, $actual);
    }

    public function testGetEnvironment(): void
    {
        $actual = $this->service->getEnvironment();
        self::assertSame(Environment::TEST, $actual);
    }

    public function testGetLocaleName(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $actual = $this->service->getLocaleName();
        self::assertStringContainsString(FormatUtils::DEFAULT_LOCALE, $actual);
    }

    public function testGetLogInfo(): void
    {
        $actual = $this->service->getLogInfo();
        self::assertStringContainsString('var', $actual);
        self::assertStringContainsString('log', $actual);
    }

    public function testGetMaintenanceStatus(): void
    {
        $actual = $this->service->getMaintenanceStatus();
        self::assertNotEmpty($actual);
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

    /**
     * @throws InvalidArgumentException
     */
    public function testGetReleaseDate(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $actual = $this->service->getReleaseDate();
        self::assertMatchesRegularExpression('/\d{4}/', $actual);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testGetRuntimePackages(): void
    {
        $actual = $this->service->getRuntimePackages();
        self::assertNotEmpty($actual);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function testGetRuntimeRoutes(): void
    {
        $actual = $this->service->getRuntimeRoutes();
        self::assertNotEmpty($actual);
    }

    public function testGetTimeZone(): void
    {
        $expected = \date_default_timezone_get();
        $actual = $this->service->getTimeZone();
        self::assertSame($expected, $actual);
    }

    public function testGetVersion(): void
    {
        $actual = $this->service->getVersion();
        self::assertSame(Kernel::VERSION, $actual);
    }

    public function testIsApcuLoaded(): void
    {
        $expected = $this->isExtensionLoaded('apcu', 'apc.enabled');
        $actual = $this->service->isApcuLoaded();
        self::assertSame($expected, $actual);
    }

    public function testIsDebug(): void
    {
        $actual = $this->service->isDebug();
        self::assertFalse($actual);
    }

    public function testIsLongTermSupport(): void
    {
        // @phpstan-ignore identical.alwaysFalse
        $expected = (4 <=> Kernel::MINOR_VERSION) === 0;
        $actual = $this->service->isLongTermSupport();
        self::assertSame($expected, $actual);
    }

    public function testIsXdebugLoaded(): void
    {
        $expected = $this->isExtensionLoaded('xdebug');
        $actual = $this->service->isXdebugLoaded();
        self::assertSame($expected, $actual);
    }

    public function testIsZendCacheLoaded(): void
    {
        $expected = $this->isExtensionLoaded('Zend OPcache', 'opcache.enable');
        $actual = $this->service->isZendCacheLoaded();
        self::assertSame($expected, $actual);
    }

    private function isExtensionLoaded(string $extension, string $enabled = ''): bool
    {
        if (!\extension_loaded($extension)) {
            return false;
        }

        return '' === $enabled || \filter_var(\ini_get($enabled), \FILTER_VALIDATE_BOOLEAN);
    }
}
