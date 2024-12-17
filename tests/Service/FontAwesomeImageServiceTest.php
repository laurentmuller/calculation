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
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class FontAwesomeImageServiceTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testInvalidDirectory(): void
    {
        $this->checkImageIsInvalid('fake', 'fake');
    }

    /**
     * @throws Exception
     */
    public function testInvalidEmptyFile(): void
    {
        $this->checkImageIsInvalid(__DIR__ . '/../data/images', 'empty.svg');
    }

    /**
     * @throws Exception
     */
    public function testInvalidFile(): void
    {
        $this->checkImageIsInvalid(__DIR__, 'fake');
    }

    /**
     * @throws Exception
     */
    public function testSvgDirectory(): void
    {
        $expected = __DIR__;
        $service = $this->createService($expected);
        $actual = $service->getSvgDirectory();
        self::assertSame($expected, $actual);
    }

    /**
     * @throws Exception
     */
    public function testValidFileSizeEquals(): void
    {
        $actual = $this->checkImageIsValid(__DIR__ . '/../data/images', '512x512.svg');
        self::assertSame(64, $actual->getWidth());
        self::assertSame(64, $actual->getHeight());
    }

    /**
     * @throws Exception
     */
    public function testValidFileWidthGreater(): void
    {
        $actual = $this->checkImageIsValid(__DIR__ . '/../data/images', '576x512.svg');
        self::assertSame(64, $actual->getWidth());
        self::assertSame(57, $actual->getHeight());
    }

    /**
     * @throws Exception
     */
    public function testValidFileWidthSmaller(): void
    {
        $actual = $this->checkImageIsValid(__DIR__ . '/../data/images', '448x512.svg');
        self::assertSame(56, $actual->getWidth());
        self::assertSame(64, $actual->getHeight());
    }

    /**
     * @throws Exception
     */
    public function testValidFileWithColor(): void
    {
        $this->checkImageIsValid(__DIR__ . '/../data/images', '448x512.svg', 'red');
    }

    /**
     * @throws Exception
     */
    public function testValidFileWithoutExtension(): void
    {
        $this->checkImageIsValid(__DIR__ . '/../data/images', '448x512');
    }

    /**
     * @throws Exception
     */
    private function checkImageIsInvalid(string $svgDirectory, string $relativePath): void
    {
        $service = $this->createService($svgDirectory);
        $actual = $service->getImage($relativePath);
        self::assertNull($actual);
    }

    /**
     * @throws Exception
     */
    private function checkImageIsValid(
        string $svgDirectory,
        string $relativePath,
        ?string $color = null
    ): FontAwesomeImage {
        $service = $this->createService($svgDirectory);
        $actual = $service->getImage($relativePath, $color);
        self::assertNotNull($actual);

        return $actual;
    }

    /**
     * @throws Exception
     */
    private function createService(string $svgDirectory): FontAwesomeImageService
    {
        return new FontAwesomeImageService(
            $svgDirectory,
            new ArrayAdapter(),
            $this->createMock(LoggerInterface::class)
        );
    }
}
