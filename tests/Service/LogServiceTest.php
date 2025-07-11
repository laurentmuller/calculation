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

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->getService(LogService::class);
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

    public function testGetLogger(): void
    {
        $actual = $this->service->getLogger();
        $actual->debug('Fake message.');
        self::expectNotToPerformAssertions();
    }

    public function testGetTranslator(): void
    {
        $actual = $this->service->getTranslator();
        $actual->trans('about.title');
        self::expectNotToPerformAssertions();
    }

    public function testIsFileValid(): void
    {
        $this->logDebug();
        $actual = $this->service->isFileValid();
        self::assertTrue($actual);
    }

    public function testParseFileInvalid(): void
    {
        $fileName = 'fake';
        $logger = $this->createMock(LoggerInterface::class);
        $translator = $this->createMockTranslator();
        $cache = new ArrayAdapter();
        $service = new LogService($fileName, $logger, $translator, $cache);

        $actual = $service->getLogFile();
        self::assertNull($actual);
    }

    public function testParseInvalidCSV(): void
    {
        $fileName = __DIR__ . '/../files/txt/log_invalid_csv.txt';
        $logger = $this->createMock(LoggerInterface::class);
        $translator = $this->createMockTranslator();
        $cache = new ArrayAdapter();
        $service = new LogService($fileName, $logger, $translator, $cache);

        $actual = $service->getLogFile();
        self::assertNotNull($actual);
        self::assertCount(0, $actual);
    }

    public function testParseInvalidJSON(): void
    {
        $fileName = __DIR__ . '/../files/txt/log_invalid_json.txt';
        $logger = $this->createMock(LoggerInterface::class);
        $translator = $this->createMockTranslator();
        $cache = new ArrayAdapter();
        $service = new LogService($fileName, $logger, $translator, $cache);

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
