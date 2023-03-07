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
    /**
     * @return array<array{0: string, 1: string, 2?: string, 3?: string}>
     */
    public static function getBuildPaths(): array
    {
        return [
            ['', ''],
            ['/', '/'],
            ['c:/', 'c:'],
            ['c:/home', 'c:', 'home'],
            ['home', 'home'],
            ['home', '', 'home', ''],
            ['home/test', 'home', 'test'],
            ['home/test/value', 'home', 'test', 'value'],
            ['home/test/value', 'home', 'test', 'value/'],
        ];
    }

    /**
     * @return array<array{0: string|\SplFileInfo|int, 1: string}>
     */
    public static function getFormatSize(): array
    {
        $kb = 1024;
        $mb = $kb * $kb;
        $gb = $mb * $kb;
        $empty = 'Empty';

        $linesFile = self::getLinesFile();
        $lineSize = \filesize($linesFile) ?: 0;

        $thisSize = \filesize(__FILE__) / $kb;
        $thisText = \sprintf('%d KB', $thisSize);

        return [
            [$linesFile, \sprintf('%d B', $lineSize)],
            [$lineSize, \sprintf('%d B', $lineSize)],

            ["D:\zzz_aaa", $empty],
            [self::getEmptyFile(), $empty],

            [0, $empty],
            [1, '1 B'],
            [$kb - 1, '1023 B'],

            [$kb, '1 KB'],
            [$kb * 2, '2 KB'],
            [$mb - 1, '1024 KB'],

            [$mb, '1.0 MB'],
            [$mb * 2, '2.0 MB'],
            [$gb - 1, '1024.0 MB'],

            [$gb, '1.0 GB'],
            [$gb * 2, '2.0 GB'],
            [$gb * $kb, '1024.0 GB'],

            [__FILE__, $thisText],

            [new \SplFileInfo("D:\zzz_aaa"), $empty],
        ];
    }

    /**
     * @return array<array{0: string|\SplFileInfo, 1: string|\SplFileInfo}>
     */
    public static function getRealPath(): array
    {
        return [
            [__DIR__, __DIR__],
            [__FILE__, __FILE__],

            [new \SplFileInfo(__DIR__), __DIR__],
            [new \SplFileInfo(__FILE__), __FILE__],
        ];
    }

    /**
     * @dataProvider getBuildPaths
     */
    public function testBuildPath(string $expected, string ...$segments): void
    {
        $actual = FileUtils::buildPath(...$segments);
        self::assertSame($expected, $actual);
    }

    public function testExist(): void
    {
        self::assertTrue(FileUtils::exists(__DIR__));
        self::assertTrue(FileUtils::exists(__FILE__));
    }

    public function testFilesystem(): void
    {
        self::assertNotNull(FileUtils::getFilesystem());
    }

    /**
     * @dataProvider getFormatSize
     */
    public function testFormatSize(string|\SplFileInfo|int $path, string $expected): void
    {
        $actual = FileUtils::formatSize($path);
        self::assertSame($expected, $actual);
    }

    public function testIsFile(): void
    {
        self::assertTrue(FileUtils::isFile(__FILE__));
    }

    public function testLineCount(): void
    {
        $empty = self::getEmptyFile();
        self::assertSame(0, FileUtils::getLinesCount($empty));
        self::assertSame(0, FileUtils::getLinesCount($empty, false));

        $lines = self::getLinesFile();
        self::assertSame(3, FileUtils::getLinesCount($lines));
        self::assertSame(6, FileUtils::getLinesCount($lines, false));
    }

    /**
     * @dataProvider getRealPath
     */
    public function testRealPath(string|\SplFileInfo $file, string $expected): void
    {
        $actual = FileUtils::realPath($file);
        self::assertSame($expected, $actual);
    }

    private static function getEmptyFile(): string
    {
        return __DIR__ . '/../Data/empty.txt';
    }

    private static function getLinesFile(): string
    {
        return __DIR__ . '/../Data/lines_count.txt';
    }
}
