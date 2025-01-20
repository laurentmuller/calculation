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

class CSVReaderTest extends TestCase
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

    /**
     * @psalm-suppress UnusedForeachValue
     */
    public function testLines(): void
    {
        $lines = 0;
        $lastKey = -1;
        $reader = $this->getReader();
        foreach ($reader as $key => $ignored) {
            ++$lines;
            $lastKey = $key;
        }
        self::assertSame(4, $lines);
        self::assertSame(3, $lastKey);
        $reader->close();
    }

    public function testReaderWithResource(): void
    {
        $resource = \fopen($this->getFileName(), 'r');
        self::assertIsResource($resource);
        $reader = new CSVReader($resource);
        self::assertTrue($reader->isOpen());
        $reader->close();
    }

    public function testReaderWithSplFileInfo(): void
    {
        $file = new \SplFileInfo($this->getFileName());
        $reader = new CSVReader($file);
        self::assertTrue($reader->isOpen());
        $reader->close();
    }

    public function testReaderWithString(): void
    {
        $file = $this->getFileName();
        $reader = new CSVReader($file);
        self::assertTrue($reader->isOpen());
        $reader->close();
    }

    private function getFileName(): string
    {
        return __DIR__ . '/../files/csv/data.csv';
    }

    private function getReader(): CSVReader
    {
        $filename = $this->getFileName();

        return new CSVReader(file: $filename, separator: self::VALUES_SEP);
    }
}
