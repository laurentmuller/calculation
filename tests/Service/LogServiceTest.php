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

use App\Service\LogService;
use App\Tests\KernelServiceTestCase;
use App\Tests\TranslatorMockTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class LogServiceTest extends KernelServiceTestCase
{
    use TranslatorMockTrait;

    private LogService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->getService(LogService::class);
    }

    public function testCacheTimeout(): void
    {
        $actual = $this->service->getCacheTimeout();
        self::assertSame(900, $actual);
    }

    public function testClearCache(): void
    {
        $this->logDebug();
        $this->service->getLogFile();
        $actual = $this->service->clearCache();
        self::assertSame($actual, $this->service);
    }

    public function testGetFileName(): void
    {
        $kernel = self::$kernel;
        self::assertNotNull($kernel);
        $expected = $kernel->getEnvironment() . '.log';
        $actual = $this->service->getFileName();
        self::assertStringEndsWith($expected, $actual);
    }

    public function testGetLogFile(): void
    {
        $this->logDebug();
        $this->service->clearCache();
        $actual = $this->service->getLogFile();
        self::assertNotNull($actual);

        $actual = $this->service->getLog(0);
        self::assertNotNull($actual);
    }

    public function testIsFileValid(): void
    {
        $this->logDebug();
        $actual = $this->service->isFileValid();
        self::assertTrue($actual);
    }

    public function testParseFileInvalid(): void
    {
        $service = new LogService('fake');
        $service->setTranslator($this->createMockTranslator());
        $service->setLogger($this->createMock(LoggerInterface::class));
        $service->setCacheItemPool(new ArrayAdapter());
        $actual = $service->getLogFile();
        self::assertNull($actual);
    }

    public function testParseInvalidCSV(): void
    {
        $fileName = __DIR__ . '/../Data/log_invalid_csv.txt';
        $service = new LogService($fileName);
        $service->setTranslator($this->createMockTranslator());
        $service->setLogger($this->createMock(LoggerInterface::class));
        $service->setCacheItemPool(new ArrayAdapter());
        $actual = $service->getLogFile();
        self::assertNotNull($actual);
        self::assertCount(0, $actual);
    }

    public function testParseInvalidJSON(): void
    {
        $fileName = __DIR__ . '/../Data/log_invalid_json.txt';
        $service = new LogService($fileName);
        $service->setTranslator($this->createMockTranslator());
        $service->setLogger($this->createMock(LoggerInterface::class));
        $service->setCacheItemPool(new ArrayAdapter());
        $actual = $service->getLogFile();
        self::assertNotNull($actual);
        self::assertCount(1, $actual);
    }

    private function logDebug(): void
    {
        $logger = $this->getService(LoggerInterface::class);
        $logger->debug('Test entry.');
    }
}
