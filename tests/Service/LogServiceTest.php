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

use App\Entity\Log;
use App\Service\LogService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class LogServiceTest extends TestCase
{
    public function testClearCache(): void
    {
        $fileName = 'fake.log';
        $service = $this->createService($fileName);
        $actual = $service->clearCache();
        self::assertSame($actual, $service);
    }

    public function testGetFileName(): void
    {
        $fileName = 'fake.log';
        $service = $this->createService($fileName);
        $actual = $service->getFileName();
        self::assertSame('fake.log', $actual);
    }

    public function testLogFileValid(): void
    {
        $fileName = __DIR__ . '/../files/log/log_valid.txt';
        $service = $this->createService($fileName);
        $actual = $service->isFileValid();
        self::assertTrue($actual);
        $actual = $service->getLogFile();
        self::assertNotNull($actual);
        $actual = $service->getLog(0);
        self::assertInstanceOf(Log::class, $actual);
    }

    public function testParseFileInvalid(): void
    {
        $fileName = 'fake.log';
        $service = $this->createService($fileName);
        $actual = $service->getLogFile();
        self::assertNull($actual);
    }

    public function testParseInvalidCSV(): void
    {
        $fileName = __DIR__ . '/../files/log/log_invalid_csv.txt';
        $service = $this->createService($fileName);
        $actual = $service->getLogFile();
        self::assertNotNull($actual);
        self::assertCount(0, $actual);
    }

    public function testParseInvalidJSON(): void
    {
        $fileName = __DIR__ . '/../files/log/log_invalid_json.txt';
        $service = $this->createService($fileName);
        $actual = $service->getLogFile();
        self::assertNotNull($actual);
        self::assertCount(1, $actual);
    }

    private function createService(string $fileName): LogService
    {
        return new LogService($fileName, new ArrayAdapter());
    }
}
