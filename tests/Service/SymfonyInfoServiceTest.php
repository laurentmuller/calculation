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
use App\Utils\FormatUtils;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpKernel\Kernel;

final class SymfonyInfoServiceTest extends TestCase
{
    public function testGetArchitecture(): void
    {
        $service = $this->createService();
        $actual = $service->getArchitecture();
        self::assertSame('64 bits', $actual);
    }

    public function testGetEndOfLife(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $service = $this->createService();
        $actual = $service->getEndOfLife();
        $year = \explode('/', Kernel::END_OF_LIFE)[1];
        self::assertStringContainsString($year, $actual);
    }

    public function testGetEndOfMaintenance(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $service = $this->createService();
        $actual = $service->getEndOfMaintenance();
        $year = \explode('/', Kernel::END_OF_MAINTENANCE)[1];
        self::assertStringContainsString($year, $actual);
    }

    public function testGetLocaleName(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $service = $this->createService();
        $actual = $service->getLocaleName();
        self::assertStringContainsString(FormatUtils::DEFAULT_LOCALE, $actual);
    }

    public function testGetMaintenanceStatus(): void
    {
        $service = $this->createService();
        $actual = $service->getMaintenanceStatus();
        self::assertNotEmpty($actual);
    }

    public function testGetReleaseDate(): void
    {
        \Locale::setDefault(FormatUtils::DEFAULT_LOCALE);
        $service = $this->createService();
        $actual = $service->getReleaseDate();
        self::assertMatchesRegularExpression('/\d{4}/', $actual);
    }

    public function testGetTimeZone(): void
    {
        $service = $this->createService();
        $expected = \date_default_timezone_get();
        $actual = $service->getTimeZone();
        self::assertSame($expected, $actual);
    }

    public function testGetVersion(): void
    {
        $service = $this->createService();
        $actual = $service->getVersion();
        self::assertSame(Kernel::VERSION, $actual);
    }

    public function testIsApcuEnabled(): void
    {
        $service = $this->createService();
        $expected = $this->isExtensionLoaded('apcu', 'apc.enabled');
        $actual = $service->isApcuEnabled();
        self::assertSame($expected, $actual);
    }

    public function testIsLongTermSupport(): void
    {
        $service = $this->createService();
        $expected = (4 <=> Kernel::MINOR_VERSION) === 0; // @phpstan-ignore-line
        $actual = $service->isLongTermSupport();
        self::assertSame($expected, $actual);
    }

    public function testIsXdebugEnabled(): void
    {
        $service = $this->createService();
        $expected = $this->isExtensionLoaded('xdebug');
        $actual = $service->isXdebugEnabled();
        self::assertSame($expected, $actual);
    }

    public function testIsZendCacheEnabled(): void
    {
        $service = $this->createService();
        $expected = $this->isExtensionLoaded('Zend OPcache', 'opcache.enable');
        $actual = $service->isOpCacheEnabled();
        self::assertSame($expected, $actual);
    }

    private function createService(): SymfonyInfoService
    {
        return new SymfonyInfoService(new ArrayAdapter());
    }

    private function isExtensionLoaded(string $extension, string $enabled = ''): bool
    {
        if (!\extension_loaded($extension)) {
            return false;
        }

        return '' === $enabled || \filter_var(\ini_get($enabled), \FILTER_VALIDATE_BOOLEAN);
    }
}
