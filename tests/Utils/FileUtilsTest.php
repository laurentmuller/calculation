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

use App\Utils\FileUtils;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(FileUtils::class)]
class FileUtilsTest extends TestCase
{
    public static function getBuildPaths(): \Iterator
    {
        yield ['', ''];
        yield ['/', '/'];
        yield ['c:/', 'c:'];
        yield ['c:/home', 'c:', 'home'];
        yield ['home', 'home'];
        yield ['home', '', 'home', ''];
        yield ['home/test', 'home', 'test'];
        yield ['home/test/value', 'home', 'test', 'value'];
        yield ['home/test/value', 'home', 'test', 'value/'];
    }

    public static function getExtension(): \Iterator
    {
        yield ['', ''];
        yield ['file', ''];
        yield ['file.', ''];
        yield ['file.txt', 'txt'];
        yield ['file.TXT', 'TXT'];
        yield ['file.TXT', 'txt', true];
    }

    public static function getFormatSize(): \Iterator
    {
        $kb = 1024;
        $mb = $kb * $kb;
        $gb = $mb * $kb;
        $empty = 'Empty';

        $linesFile = self::getLinesFile();
        $lineSize = (int) \filesize($linesFile);

        $thisSize = \round(\filesize(__FILE__) / $kb);
        $thisText = \sprintf('%d KB', $thisSize);
        yield [$linesFile, \sprintf('%d B', $lineSize)];
        yield [$lineSize, \sprintf('%d B', $lineSize)];
        yield ["D:\zzz_aaa", $empty];
        yield [self::getEmptyFile(), $empty];
        yield [0, $empty];
        yield [1, '1 B'];
        yield [$kb - 1, '1023 B'];
        yield [$kb, '1 KB'];
        yield [$kb * 2, '2 KB'];
        yield [$mb - 1, '1024 KB'];
        yield [$mb, '1.0 MB'];
        yield [$mb * 2, '2.0 MB'];
        yield [$gb - 1, '1024.0 MB'];
        yield [$gb, '1.0 GB'];
        yield [$gb * 2, '2.0 GB'];
        yield [$gb * $kb, '1.0 TB'];
        yield [__FILE__, $thisText];
        yield [new \SplFileInfo("D:\zzz_aaa"), $empty];
    }

    public static function getRealPath(): \Iterator
    {
        yield [__DIR__, __DIR__];
        yield [__FILE__, __FILE__];
        yield [new \SplFileInfo(__DIR__), __DIR__];
        yield [new \SplFileInfo(__FILE__), __FILE__];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getBuildPaths')]
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

    #[\PHPUnit\Framework\Attributes\DataProvider('getFormatSize')]
    public function testFormatSize(string|\SplFileInfo|int $path, string $expected): void
    {
        $actual = FileUtils::formatSize($path);
        self::assertSame($expected, $actual);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getExtension')]
    public function testGetExtension(string $file, string $expected, bool $forceLowerCase = false): void
    {
        $actual = FileUtils::getExtension($file, $forceLowerCase);
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

    #[\PHPUnit\Framework\Attributes\DataProvider('getRealPath')]
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
