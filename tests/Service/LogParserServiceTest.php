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
use App\Model\LogFile;
use App\Model\LogFileEntry;
use App\Service\LogParserService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\DatePoint;

final class LogParserServiceTest extends TestCase
{
    public function testFileNameValid(): void
    {
        $fileName = __DIR__ . '/../files/log/log_valid.txt';
        $actual = $this->parse($fileName);
        self::assertCount(4, $actual);
        $actual = $actual->getLog(0);
        self::assertInstanceOf(Log::class, $actual);
    }

    public function testLogEntryValid(): void
    {
        $entry = new LogFileEntry(
            'log_valid.txt',
            __DIR__ . '/../files/log/log_valid.txt',
            new DatePoint()
        );
        $actual = $this->parse($entry);
        self::assertCount(4, $actual);
        $actual = $actual->getLog(0);
        self::assertInstanceOf(Log::class, $actual);
    }

    public function testParseInvalidCSV(): void
    {
        $fileName = __DIR__ . '/../files/log/log_invalid_csv.txt';
        $actual = $this->parse($fileName);
        self::assertCount(0, $actual);
    }

    public function testParseInvalidJSON(): void
    {
        $fileName = __DIR__ . '/../files/log/log_invalid_json.txt';
        $actual = $this->parse($fileName);
        self::assertCount(1, $actual);
    }

    private function parse(LogFileEntry|string $fileName): LogFile
    {
        $service = new LogParserService();

        return $service->parseFile($fileName);
    }
}
