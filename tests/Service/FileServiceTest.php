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

namespace App\Tests\Service;

use App\Enums\ImageExtension;
use App\Service\FileService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

final class FileServiceTest extends TestCase
{
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

    public function testDecodeJsonEmptyFile(): void
    {
        self::expectException(\InvalidArgumentException::class);
        $service = $this->createFileService();
        $service->decodeJson(self::getEmptyFile());
    }

    public function testDecodeJsonInvalidFile(): void
    {
        $service = $this->createFileService();
        self::expectException(\InvalidArgumentException::class);
        $service->decodeJson($this->getFakeFile());
    }

    public function testDecodeJsonValid(): void
    {
        $expected = 10;
        $service = $this->createFileService();
        $actual = $service->decodeJson($this->getJsonFile());
        self::assertCount($expected, $actual);
    }

    public function testDumFile(): void
    {
        $service = $this->createFileService();
        $file = $service->tempFile();
        self::assertIsString($file);
        $actual = $service->dumpFile($file, 'My Content');
        self::assertTrue($actual);
    }

    public function testDumpFileInvalid(): void
    {
        $file = '///.txt';
        $service = $this->createFileService();
        $actual = $service->dumpFile($file, 'fake');
        self::assertFalse($actual);
    }

    public function testFileCopyFail(): void
    {
        $originFile = $this->getFakeFile();
        $targetFile = $originFile . '.copy';
        $service = $this->createFileService();

        try {
            $actual = $service->copy($originFile, $targetFile);
            self::assertFalse($actual);
        } finally {
            $service->remove($originFile);
        }
    }

    public function testFileCopySuccess(): void
    {
        $service = $this->createFileService();
        $originFile = $service->tempFile();
        self::assertIsString($originFile);
        $targetFile = $originFile . '.copy';

        try {
            $actual = $service->copy($originFile, $targetFile);
            self::assertTrue($actual);
        } finally {
            $service->remove($originFile);
            $service->remove($targetFile);
        }
    }

    /**
     * @param string|non-negative-int $path
     */
    #[DataProvider('getFormatSize')]
    public function testFormatSize(string|int $path, string $expected): void
    {
        $service = $this->createFileService();
        $actual = $service->formatSize($path);
        self::assertSame($expected, $actual);
    }

    public function testLineCount(): void
    {
        $service = $this->createFileService();
        $empty = self::getEmptyFile();
        self::assertSame(0, $service->getLinesCount($empty));
        self::assertSame(0, $service->getLinesCount($empty, false));

        $lines = self::getLinesFile();
        self::assertSame(3, $service->getLinesCount($lines));
        self::assertSame(6, $service->getLinesCount($lines, false));
    }

    public function testMakePathRelative(): void
    {
        $service = $this->createFileService();
        $expected = 'videos';
        $actual = $service->makePathRelative('/tmp/videos', '/tmp');
        self::assertSame($expected, $actual);

        $endPath = __FILE__;
        $startPath = __DIR__;
        $expected = \basename($endPath);
        $actual = $service->makePathRelative($endPath, $startPath);
        self::assertSame($expected, $actual);
    }

    public function testMirror(): void
    {
        $service = $this->createFileService();
        $source = $service->tempDir(__DIR__);
        $target = $service->tempDir(__DIR__);
        self::assertIsString($source);
        self::assertIsString($target);

        try {
            $actual = $service->mirror($source, $target);
            self::assertTrue($actual);
        } finally {
            $service->remove($source);
            $service->remove($target);
        }
    }

    public function testMirrorFail(): void
    {
        $service = $this->createFileService();
        $source = __DIR__ . '/source';
        $target = __DIR__ . '/target';
        $actual = $service->mirror($source, $target);
        self::assertFalse($actual);
    }

    public function testReadFileInvalidDirectory(): void
    {
        $service = $this->createFileService();
        $actual = $service->readFile(__DIR__);
        self::assertNull($actual);
    }

    public function testReadFileInvalidUrl(): void
    {
        $service = $this->createFileService();
        $actual = $service->readFile('https://example.com/fake.txt');
        self::assertNull($actual);
    }

    public function testReadFileValid(): void
    {
        $service = $this->createFileService();
        $content = $service->readFile($this->getJsonFile());
        self::assertNotEmpty($content);
    }

    public function testRemove(): void
    {
        $service = $this->createFileService();
        $file = $service->tempFile();
        self::assertIsString($file);
        $actual = $service->remove($file);
        self::assertTrue($actual);

        $file = $this->getFakeFile();
        $actual = $service->remove($file);
        self::assertFalse($actual);

        $file = $service->tempFile();
        self::assertIsString($file);

        $actual = $service->remove($file);
        self::assertTrue($actual);
    }

    public function testRename(): void
    {
        $service = $this->createFileService();
        $file = $service->tempFile();
        self::assertIsString($file);
        $actual = $service->rename($file, $file);
        self::assertFalse($actual);

        $target = ImageExtension::PNG->changeExtension($file);
        $actual = $service->rename($file, $target);
        $service->remove($target);
        self::assertTrue($actual);
    }

    public function testSize(): void
    {
        $service = $this->createFileService();
        $file = $this->getFakeFile();
        $actual = $service->size($file);
        self::assertSame(0, $actual);

        $file = self::getEmptyFile();
        $actual = $service->size($file);
        self::assertSame(0, $actual);

        $file = __FILE__;
        $actual = $service->size($file);
        $expected = \filesize($file);
        self::assertSame($expected, $actual);

        $file = __DIR__;
        $actual = $service->size($file);
        self::assertGreaterThan(0, $actual);
    }

    public function testSizeAndFiles(): void
    {
        $service = $this->createFileService();
        $path = __DIR__;
        $actual = $service->sizeAndFiles($path);
        self::assertGreaterThan(0, $actual['size']);
        self::assertGreaterThan(0, $actual['files']);
    }

    public function testSizeAndFilesNotDirectory(): void
    {
        $service = $this->createFileService();
        $path = __FILE__;
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Path "' . $path . '" is not a directory.');
        $service->sizeAndFiles($path);
    }

    public function testSizeAndFilesNotExist(): void
    {
        $service = $this->createFileService();
        $path = $this->getFakeFile();
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Path "' . $path . '" does not exist.');
        $service->sizeAndFiles($path);
    }

    public function testTempDir(): void
    {
        $service = $this->createFileService();
        $actual = $service->tempDir(__DIR__);
        self::assertNotNull($actual);
    }

    public function testTempDirInvalid(): void
    {
        $service = $this->createFileService();
        $actual = @$service->tempDir('///.txt');
        self::assertNull($actual);
    }

    public function testTempDirInvalidUrl(): void
    {
        $service = $this->createFileService();
        $actual = $service->tempDir('https://example.com');
        self::assertNull($actual);
    }

    public function testTempFile(): void
    {
        $service = $this->createFileService();
        $dir = $service->tempFile(prefix: __DIR__);
        self::assertNotNull($dir);
    }

    public function testTempFileInvalid(): void
    {
        $service = $this->createFileService();
        $actual = $service->tempFile(dir: 'https://example.com', prefix: 'https://example.com');
        self::assertNull($actual);
    }

    private function createFileService(): FileService
    {
        return new FileService(
            new Filesystem(),
            self::createStub(LoggerInterface::class),
        );
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
