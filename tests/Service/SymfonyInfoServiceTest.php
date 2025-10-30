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

use App\Service\SymfonyInfoService;
use App\Tests\KernelServiceTestCase;
use App\Utils\FormatUtils;
use Symfony\Component\HttpKernel\Kernel;

final class SymfonyInfoServiceTest extends KernelServiceTestCase
{
    private SymfonyInfoService $service;

    #[\Override]
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

    public function testGetLocaleName(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $actual = $this->service->getLocaleName();
        self::assertStringContainsString(FormatUtils::DEFAULT_LOCALE, $actual);
    }

    public function testGetMaintenanceStatus(): void
    {
        $actual = $this->service->getMaintenanceStatus();
        self::assertNotEmpty($actual);
    }

    public function testGetReleaseDate(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $actual = $this->service->getReleaseDate();
        self::assertMatchesRegularExpression('/\d{4}/', $actual);
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

    public function testIsApcuEnabled(): void
    {
        $expected = $this->isExtensionLoaded('apcu', 'apc.enabled');
        $actual = $this->service->isApcuEnabled();
        self::assertSame($expected, $actual);
    }

    public function testIsLongTermSupport(): void
    {
        // @phpstan-ignore identical.alwaysFalse
        $expected = (4 <=> Kernel::MINOR_VERSION) === 0;
        $actual = $this->service->isLongTermSupport();
        self::assertSame($expected, $actual);
    }

    public function testIsXdebugEnabled(): void
    {
        $expected = $this->isExtensionLoaded('xdebug');
        $actual = $this->service->isXdebugEnabled();
        self::assertSame($expected, $actual);
    }

    public function testIsZendCacheEnabled(): void
    {
        $expected = $this->isExtensionLoaded('Zend OPcache', 'opcache.enable');
        $actual = $this->service->isOpCacheEnabled();
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
