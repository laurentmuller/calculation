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

use App\Util\ReverseReader;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for the {@link ReverseReader} class.
 *
 * @see ReverseReader
 */
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
    }

    public function testLines(): void
    {
        $reader = $this->getReader();
        for ($i = 3; $i >= 1; --$i) {
            $line = $reader->current();
            self::assertSame("Line $i", $line);
        }
        self::assertNull($reader->current());
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
