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

use App\Model\FontAwesomeImage;
use App\Service\FontAwesomeImageService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class FontAwesomeImageServiceTest extends TestCase
{
    public function testAliases(): void
    {
        $svgDirectory = __DIR__ . '/../files/json';
        $service = $this->createService($svgDirectory);
        $actual = $service->getAliases();
        self::assertCount(2, $actual);
    }

    public function testInvalidDirectory(): void
    {
        $this->checkImageIsInvalid('fake', 'fake', false);
    }

    public function testInvalidEmptyFile(): void
    {
        $this->checkImageIsInvalid(__DIR__ . '/../files/images', 'empty.svg');
    }

    public function testInvalidFile(): void
    {
        $this->checkImageIsInvalid(__DIR__, 'fake');
    }

    public function testSvgDirectory(): void
    {
        $expected = __DIR__;
        $service = $this->createService($expected);
        $actual = $service->getSvgDirectory();
        self::assertSame($expected, $actual);
    }

    public function testValidDirectory(): void
    {
        $directory = __DIR__ . '/../files/images';
        $directory = $this->validateDirectory($directory);
        $file = $directory . '/512x512.svg';
        self::assertFileExists($file);
    }

    public function testValidFileSizeEquals(): void
    {
        $actual = $this->checkImageIsValid(__DIR__ . '/../files/images', '512x512.svg');
        self::assertSame(64, $actual->getWidth());
        self::assertSame(64, $actual->getHeight());
    }

    public function testValidFileWidthGreater(): void
    {
        $actual = $this->checkImageIsValid(__DIR__ . '/../files/images', '576x512.svg');
        self::assertSame(64, $actual->getWidth());
        self::assertSame(57, $actual->getHeight());
    }

    public function testValidFileWidthSmaller(): void
    {
        $actual = $this->checkImageIsValid(__DIR__ . '/../files/images', '448x512.svg');
        self::assertSame(56, $actual->getWidth());
        self::assertSame(64, $actual->getHeight());
    }

    public function testValidFileWithColor(): void
    {
        $this->checkImageIsValid(__DIR__ . '/../files/images', '448x512.svg', 'red');
    }

    public function testValidFileWithoutExtension(): void
    {
        $this->checkImageIsValid(__DIR__ . '/../files/images', '448x512');
    }

    private function checkImageIsInvalid(string $svgDirectory, string $relativePath, bool $realPath = true): void
    {
        if ($realPath) {
            $svgDirectory = $this->validateDirectory($svgDirectory);
        }
        $service = $this->createService($svgDirectory);
        $actual = $service->getImage($relativePath);
        self::assertNull($actual);
    }

    private function checkImageIsValid(
        string $svgDirectory,
        string $relativePath,
        ?string $color = null
    ): FontAwesomeImage {
        $svgDirectory = $this->validateDirectory($svgDirectory);
        $service = $this->createService($svgDirectory);
        $actual = $service->getImage($relativePath, $color);
        self::assertNotNull($actual);

        return $actual;
    }

    private function createService(string $svgDirectory): FontAwesomeImageService
    {
        return new FontAwesomeImageService(
            $svgDirectory,
            new ArrayAdapter()
        );
    }

    private function validateDirectory(string $svgDirectory): string
    {
        $svgDirectory = \realpath($svgDirectory);
        self::assertIsString($svgDirectory);
        self::assertDirectoryExists($svgDirectory);

        return $svgDirectory;
    }
}
