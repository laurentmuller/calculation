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

namespace App\Tests\Utils;

use App\Util\CSVReader;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for the {@link CSVReader} class.
 *
 * @see CSVReader
 */
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
        $reader = $this->getReader();
        foreach ($reader as $ignored) {
            ++$lines;
        }
        self::assertSame(4, $lines);
        self::assertSame(4, $reader->key());
        $reader->close();
    }

    public function testRewind(): void
    {
        $reader = $this->getReader();
        $reader->next();
        self::assertSame(1, $reader->key());
        $reader->rewind();
        self::assertSame(0, $reader->key());
        $reader->close();
    }

    private function getFileName(): string
    {
        return __DIR__ . '/../Data/csv_reader.csv';
    }

    private function getReader(): CSVReader
    {
        $filename = $this->getFileName();

        return new CSVReader(filename: $filename, separator: self::VALUES_SEP);
    }
}
