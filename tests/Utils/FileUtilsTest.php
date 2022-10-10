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
     * @dataProvider getBuildPaths
     */
    public function testBuildPath(string $expected, string ...$segments): void
    {
        $actual = FileUtils::buildPath(...$segments);
        self::assertEquals($expected, $actual);
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

    public function testFormatSize(): void
    {
        $file = $this->getLinesFile();
        $size = \filesize($file);
        self::assertTrue(FileUtils::exists($file));
        self::assertEquals($size . ' B', FileUtils::formatSize($file));
    }

    public function testIsFile(): void
    {
        self::assertTrue(FileUtils::isFile(__FILE__));
    }

    public function testLineCount(): void
    {
        $empty = $this->getEmptyFile();
        self::assertEquals(0, FileUtils::getLinesCount($empty));
        self::assertEquals(0, FileUtils::getLinesCount($empty, false));

        $lines = $this->getLinesFile();
        self::assertEquals(3, FileUtils::getLinesCount($lines));
        self::assertEquals(6, FileUtils::getLinesCount($lines, false));
    }

    private function getEmptyFile(): string
    {
        return __DIR__ . '/../Data/empty.txt';
    }

    private function getLinesFile(): string
    {
        return __DIR__ . '/../Data/lines_count.txt';
    }
}
