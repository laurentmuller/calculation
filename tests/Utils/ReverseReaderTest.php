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

use App\Utils\ReverseReader;
use PHPUnit\Framework\TestCase;

class ReverseReaderTest extends TestCase
{
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
        $index = 3;
        $reader = $this->getReader();
        foreach ($reader as $line) {
            self::assertSame("Line $index", $line);
            --$index;
        }
        $reader->close();
    }

    private function getFileName(): string
    {
        return __DIR__ . '/../Data/reverse_reader.txt';
    }

    private function getReader(): ReverseReader
    {
        $filename = $this->getFileName();

        return new ReverseReader($filename);
    }
}
