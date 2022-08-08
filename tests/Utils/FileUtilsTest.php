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

use App\Util\FileUtils;
use PHPUnit\Framework\TestCase;

/**
 * Unit test for the Unit test for {@link FileUtils} class.
 */
class FileUtilsTest extends TestCase
{
    public function getBuildPaths(): array
    {
        return [
            ['', ''],
            ['home', 'home'],
            ['home' . \DIRECTORY_SEPARATOR . 'test', 'home', 'test'],
            ['home' . \DIRECTORY_SEPARATOR . 'test' . \DIRECTORY_SEPARATOR . 'value', 'home', 'test', 'value'],
        ];
    }

    /**
     * @dataProvider getBuildPaths
     */
    public function testBuildPath(string $expected, string ...$segments): void
    {
        switch (\count($segments)) {
            case 1:
                $actual = FileUtils::buildPath($segments[0]);
                self::assertSame($expected, $actual);
                break;
            case 2:
                $actual = FileUtils::buildPath($segments[0], $segments[1]);
                self::assertSame($expected, $actual);
                break;
            case 3:
                $actual = FileUtils::buildPath($segments[0], $segments[1], $segments[2]);
                self::assertSame($expected, $actual);
                break;
        }
    }

    public function testExist(): void
    {
        self::assertTrue(FileUtils::exists(__FILE__));
    }

    public function testFilesystem(): void
    {
        self::assertNotNull(FileUtils::getFilesystem());
    }

    public function testFormatSize(): void
    {
        $file = $this->getReaderFile();
        self::assertTrue(FileUtils::exists($file));
        self::assertSame('21 B', FileUtils::formatSize($file));
    }

    public function testIsFile(): void
    {
        self::assertTrue(FileUtils::isFile(__FILE__));
    }

    public function testLineCount(): void
    {
        $file = $this->getReaderFile();
        self::assertTrue(FileUtils::exists($file));
        self::assertSame(4, FileUtils::getLinesCount($file));
    }

    private function getReaderFile(): string
    {
        return __DIR__ . '/../Data/reverse_reader.txt';
    }
}
