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

use App\Enums\ImageExtension;
use App\Tests\PrivateInstanceTrait;
use App\Utils\FileUtils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FileUtilsTest extends TestCase
{
    use PrivateInstanceTrait;

    /**
     * @phpstan-return \Generator<int, array{0: string, 1: string, 2?: string, 3?: string}>
     */
    public static function getBuildPaths(): \Generator
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

    /**
     * @phpstan-return \Generator<int, array{0: string, 1: string, 2?: true}>
     */
    public static function getExtension(): \Generator
    {
        yield ['', ''];
        yield ['file', ''];
        yield ['file.', ''];
        yield ['file.txt', 'txt'];
        yield ['file.TXT', 'TXT'];
        yield ['file.TXT', 'txt', true];
    }

    /**
     * @phpstan-return \Generator<int, array{string|\SplFileInfo|non-negative-int, string}>
     */
    public static function getFormatSize(): \Generator
    {
        $kb = 1024;
        $mb = $kb * $kb;
        $gb = $mb * $kb;
        $empty = '0 B';

        $emptyFile = self::getEmptyFile();
        $fakeFile = __DIR__ . '/fake.txt';

        $linesFile = self::getLinesFile();
        $lineSize = (int) \filesize($linesFile);
        $lineText = \sprintf('%d B', $lineSize);

        $thisSize = \floor((int) \filesize(__FILE__) / $kb);
        $thisText = \sprintf('%d KiB', $thisSize);

        yield [0, $empty];
        yield [$fakeFile, $empty];
        yield [$emptyFile, $empty];

        yield [$lineSize, $lineText];
        yield [$linesFile, $lineText];

        yield [1, '1 B'];
        yield [100, '100 B'];
        yield [1000, '1000 B'];

        yield [$kb, '1 KiB'];
        yield [$kb - 1, '1023 B'];
        yield [$kb * 2, '2 KiB'];

        yield [$mb, '1.0 MiB'];
        yield [$mb - 1, '1023 KiB'];
        yield [$mb * 2, '2.0 MiB'];

        yield [$gb, '1.0 GiB'];
        yield [$gb - 1, '1024.0 MiB'];
        yield [$gb * 2, '2.0 GiB'];

        yield [$gb * $kb, '1024.0 GiB'];

        yield [__FILE__, $thisText];
    }

    /**
     * @phpstan-return \Generator<int, array{string|\SplFileInfo, string}>
     */
    public static function getRealPath(): \Generator
    {
        yield [__DIR__, __DIR__];
        yield [__FILE__, __FILE__];
        yield [new \SplFileInfo(__DIR__), __DIR__];
        yield [new \SplFileInfo(__FILE__), __FILE__];
    }

    #[DataProvider('getBuildPaths')]
    public function testBuildPath(string $expected, string ...$segments): void
    {
        $actual = FileUtils::buildPath(...$segments);
        self::assertSame($expected, $actual);
    }

    public function testChangeExtension(): void
    {
        $expected = 'test.png';
        $old_name = 'test.jpeg';
        $actual = FileUtils::changeExtension($old_name, 'png');
        self::assertSame($expected, $actual);

        $expected = 'test.bmp';
        $actual = FileUtils::changeExtension($old_name, ImageExtension::BMP);
        self::assertSame($expected, $actual);
    }

    public function testChmod(): void
    {
        $file = FileUtils::tempFile();
        self::assertIsString($file);

        try {
            $actual = FileUtils::chmod($file, 1);
            self::assertTrue($actual);
        } finally {
            FileUtils::remove($file);
        }
    }

    public function testChmodInvalid(): void
    {
        $file = __DIR__ . '/fake.txt';
        $actual = FileUtils::chmod($file, 1);
        self::assertFalse($actual);
    }

    public function testDecodeJsonEmptyFile(): void
    {
        self::expectException(\InvalidArgumentException::class);
        FileUtils::decodeJson(self::getEmptyFile());
    }

    public function testDecodeJsonInvalidFile(): void
    {
        self::expectException(\InvalidArgumentException::class);
        FileUtils::decodeJson($this->getFakeFile());
    }

    public function testDecodeJsonValid(): void
    {
        $expected = 10;
        $actual = FileUtils::decodeJson($this->getJsonFile());
        self::assertCount($expected, $actual);
    }

    public function testDumFile(): void
    {
        $file = FileUtils::tempFile();
        self::assertIsString($file);
        $actual = FileUtils::dumpFile($file, 'My Content');
        self::assertTrue($actual);
    }

    public function testDumpFileInvalid(): void
    {
        $file = '///.txt';
        $actual = FileUtils::dumpFile($file, 'fake');
        self::assertFalse($actual);
    }

    public function testExist(): void
    {
        self::assertTrue(FileUtils::exists(__DIR__));
        self::assertTrue(FileUtils::exists(__FILE__));
    }

    public function testFileCopyFail(): void
    {
        $originFile = $this->getFakeFile();
        $targetFile = $originFile . '.copy';

        try {
            $actual = FileUtils::copy($originFile, $targetFile);
            self::assertFalse($actual);
        } finally {
            FileUtils::remove($originFile);
        }
    }

    public function testFileCopySuccess(): void
    {
        $originFile = FileUtils::tempFile();
        self::assertIsString($originFile);
        $targetFile = $originFile . '.copy';

        try {
            $actual = FileUtils::copy($originFile, $targetFile);
            self::assertTrue($actual);
        } finally {
            FileUtils::remove($originFile);
            FileUtils::remove($targetFile);
        }
    }

    /**
     * @phpstan-param string|\SplFileInfo|non-negative-int $path
     */
    #[DataProvider('getFormatSize')]
    public function testFormatSize(string|\SplFileInfo|int $path, string $expected): void
    {
        $actual = FileUtils::formatSize($path);
        self::assertSame($expected, $actual);
    }

    #[DataProvider('getExtension')]
    public function testGetExtension(string $file, string $expected, bool $forceLowerCase = false): void
    {
        $actual = FileUtils::getExtension($file, $forceLowerCase);
        self::assertSame($expected, $actual);
    }

    public function testIsDir(): void
    {
        self::assertTrue(FileUtils::isDir(__DIR__));
        self::assertFalse(FileUtils::isDir(__FILE__));
    }

    public function testIsFile(): void
    {
        self::assertFalse(FileUtils::isFile(__DIR__));
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

    public function testMakePathRelative(): void
    {
        $expected = 'videos';
        $actual = FileUtils::makePathRelative('/tmp/videos', '/tmp');
        self::assertSame($expected, $actual);

        $endPath = __FILE__;
        $startPath = __DIR__;
        $expected = \basename($endPath);
        $actual = FileUtils::makePathRelative($endPath, $startPath);
        self::assertSame($expected, $actual);
    }

    public function testMirror(): void
    {
        $source = FileUtils::tempDir(__DIR__);
        $target = FileUtils::tempDir(__DIR__);
        self::assertIsString($source);
        self::assertIsString($target);

        try {
            $actual = FileUtils::mirror($source, $target);
            self::assertTrue($actual);
        } finally {
            FileUtils::remove($source);
            FileUtils::remove($target);
        }
    }

    public function testMirrorFail(): void
    {
        $source = __DIR__ . '/source';
        $target = __DIR__ . '/target';
        $actual = FileUtils::mirror($source, $target);
        self::assertFalse($actual);
    }

    public function testMkdirInvalid(): void
    {
        $file = '///.txt';
        $actual = FileUtils::mkdir($file);
        self::assertFalse($actual);
    }

    public function testNormalize(): void
    {
        $expected = 'C:/Temp/';
        $actual = FileUtils::normalize('C:\\Temp\\');
        self::assertSame($expected, $actual);
    }

    /**
     * @throws \ReflectionException
     */
    public function testPrivateInstance(): void
    {
        self::assertPrivateInstance(FileUtils::class);
    }

    public function testReadFileInvalidDirectory(): void
    {
        $expected = '';
        $actual = FileUtils::readFile(__DIR__);
        self::assertSame($expected, $actual);
    }

    public function testReadFileInvalidUrl(): void
    {
        $expected = '';
        $actual = FileUtils::readFile('https://example.com/fake.txt');
        self::assertSame($expected, $actual);
    }

    public function testReadFileValid(): void
    {
        $content = FileUtils::readFile($this->getJsonFile());
        self::assertNotEmpty($content);
    }

    #[DataProvider('getRealPath')]
    public function testRealPath(string|\SplFileInfo $file, string $expected): void
    {
        $actual = FileUtils::realPath($file);
        self::assertSame($expected, $actual);
    }

    public function testRemove(): void
    {
        $file = FileUtils::tempFile();
        self::assertIsString($file);
        $actual = FileUtils::remove($file);
        self::assertTrue($actual);

        $file = $this->getFakeFile();
        $actual = FileUtils::remove($file);
        self::assertFalse($actual);
    }

    public function testRename(): void
    {
        $file = FileUtils::tempFile();
        self::assertIsString($file);
        $actual = FileUtils::rename($file, $file);
        self::assertFalse($actual);

        $target = FileUtils::changeExtension($file, 'png');
        $actual = FileUtils::rename($file, $target);
        FileUtils::remove($target);
        self::assertTrue($actual);
    }

    public function testSize(): void
    {
        $file = $this->getFakeFile();
        $actual = FileUtils::size($file);
        self::assertSame(0, $actual);

        $file = self::getEmptyFile();
        $actual = FileUtils::size($file);
        self::assertSame(0, $actual);

        $file = __FILE__;
        $actual = FileUtils::size($file);
        $expected = \filesize($file);
        self::assertSame($expected, $actual);

        $file = __DIR__;
        $actual = FileUtils::size($file);
        self::assertGreaterThan(0, $actual);
    }

    public function testTempDir(): void
    {
        $actual = FileUtils::tempDir(__DIR__);
        self::assertNotNull($actual);
    }

    public function testTempDirInvalid(): void
    {
        $actual = FileUtils::tempDir('///.txt');
        self::assertNull($actual);
    }

    public function testTempDirInvalidUrl(): void
    {
        $actual = FileUtils::tempDir('https://example.com');
        self::assertNull($actual);
    }

    public function testTempFile(): void
    {
        $dir = FileUtils::tempFile(prefix: __DIR__);
        self::assertNotNull($dir);
    }

    public function testTempFileInvalid(): void
    {
        $actual = FileUtils::tempFile(dir: 'https://example.com', prefix: 'https://example.com');
        self::assertNull($actual);
    }

    private static function getEmptyFile(): string
    {
        return __DIR__ . '/../files/txt/empty.txt';
    }

    private function getFakeDir(): string
    {
        return __DIR__ . '/fake:Dir';
    }

    private function getFakeFile(): string
    {
        return $this->getFakeDir() . '/fake.txt';
    }

    private function getJsonFile(): string
    {
        return __DIR__ . '/../files/city/list.json';
    }

    private static function getLinesFile(): string
    {
        return __DIR__ . '/../files/txt/lines_count.txt';
    }
}
