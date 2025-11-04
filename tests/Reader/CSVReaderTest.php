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

use App\Reader\CSVReader;
use PHPUnit\Framework\TestCase;

final class CSVReaderTest extends TestCase
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

    public function testInvalidEnclosure(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Field enclosure character must be a single byte character.');
        CSVReader::instance(file: $this->getFileName(), enclosure: 'fake');
    }

    public function testInvalidEscape(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Escape character must be a single byte character or an empty string.');
        CSVReader::instance(file: $this->getFileName(), escape: 'fake');
    }

    public function testInvalidSeparator(): void
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Field separator must be a single byte character.');
        CSVReader::instance(file: $this->getFileName(), separator: 'fake');
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

    public function testWithFileInfo(): void
    {
        $file = new \SplFileInfo($this->getFileName());
        $reader = CSVReader::instance($file);
        self::assertTrue($reader->isOpen());
        $reader->close();
    }

    public function testWithFileResource(): void
    {
        $resource = null;

        try {
            $resource = \fopen($this->getFileName(), 'r');
            self::assertIsResource($resource);
            $reader = CSVReader::instance($resource);
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
        $reader = CSVReader::instance($file);
        self::assertTrue($reader->isOpen());
        $reader->close();
    }

    private function getFileName(): string
    {
        return __DIR__ . '/../files/csv/data.csv';
    }

    private function getReader(): CSVReader
    {
        return CSVReader::instance(file: $this->getFileName(), separator: self::VALUES_SEP);
    }
}
