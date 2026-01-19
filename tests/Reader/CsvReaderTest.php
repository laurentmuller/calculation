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

namespace App\Tests\Reader;

use App\Reader\CsvReader;
use App\Service\CsvService;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

final class CsvReaderTest extends TestCase
{
    private const VALUES_SEP = '|';

    public function testContent(): void
    {
        $reader = $this->getReader();
        foreach ($reader as $data) {
            self::assertCount(6, $data);
        }
        $reader->close();
    }

    public function testFileExist(): void
    {
        $filename = $this->getFileName();
        self::assertFileExists($filename);
        self::assertFileIsReadable($filename);
    }

    public function testIsOpen(): void
    {
        $reader = $this->getReader();
        self::assertTrue($reader->isOpen());
        $reader->close();
        self::assertFalse($reader->isOpen());
    }

    public function testLines(): void
    {
        $lines = 0;
        $index = 0;
        $reader = $this->getReader();
        foreach ($reader as $key => $data) {
            self::assertSame($index++, $key);
            self::assertCount(6, $data);
            ++$lines;
        }
        $reader->close();
        self::assertSame(4, $lines);
    }

    /**
     * @param int<1, max> $lines
     */
    #[TestWith([1, 1])]
    #[TestWith([4, 4])]
    #[TestWith([10, 4])]
    public function testSkip(int $lines, int $expected): void
    {
        $reader = $this->getReader();
        $actual = $reader->skip($lines);
        self::assertSame($expected, $actual);
    }

    public function testSkipAndKeys(): void
    {
        $reader = $this->getReader();
        $index = $reader->skip();
        self::assertSame(1, $index);
        foreach ($reader as $key => $data) {
            self::assertSame($index++, $key);
        }
    }

    public function testWithFileResource(): void
    {
        $resource = null;

        try {
            $resource = \fopen($this->getFileName(), 'r');
            self::assertIsResource($resource);
            $reader = CsvReader::instance($resource);
            self::assertTrue($reader->isOpen());
            $reader->close();
        } finally {
            if (\is_resource($resource)) {
                \fclose($resource);
            }
        }
    }

    public function testWithFileString(): void
    {
        $file = $this->getFileName();
        $reader = CsvReader::instance($file);
        self::assertTrue($reader->isOpen());
        $reader->close();
    }

    private function getFileName(): string
    {
        return __DIR__ . '/../files/csv/data.csv';
    }

    private function getReader(): CsvReader
    {
        $file = $this->getFileName();
        $service = new CsvService(separator: self::VALUES_SEP);

        return CsvReader::instance($file, $service);
    }
}
