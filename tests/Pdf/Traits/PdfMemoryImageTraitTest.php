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

namespace App\Tests\Pdf\Traits;

use App\Service\ImageService;
use App\Tests\Data\PdfImageDocument;
use fpdf\PdfException;
use PHPUnit\Framework\TestCase;

class PdfMemoryImageTraitTest extends TestCase
{
    public function testImageAvif(): void
    {
        $file = $this->getImagePath('avif');
        $doc = $this->createDocument();
        $doc->imageAvif($file);
        self::assertSame(1, $doc->getPage());
    }

    public function testImageBmp(): void
    {
        $file = $this->getImagePath('bmp');
        $doc = $this->createDocument();
        $doc->imageBmp($file);
        self::assertSame(1, $doc->getPage());
    }

    public function testImageData(): void
    {
        $file = $this->getImagePath('png');
        $data = \file_get_contents($file);
        self::assertIsString($data);

        $doc = $this->createDocument();
        $doc->imageData($data);
        self::assertSame(1, $doc->getPage());
    }

    public function testImageGD(): void
    {
        $doc = $this->createDocument();
        $image = $this->createGdImage();
        $doc->imageGD($image);
        self::assertSame(1, $doc->getPage());
    }

    public function testImageGDInvalid(): void
    {
        self::expectException(PdfException::class);
        $image = \imagecreate(100, 100);
        self::assertInstanceOf(\GdImage::class, $image);
        $doc = new PdfImageDocument();
        \imagedestroy($image);
        $doc->imageGD($image);
    }

    public function testImageWbmp(): void
    {
        $file = $this->getImagePath('wbmp');
        $doc = $this->createDocument();
        $doc->imageWbmp($file);
        self::assertSame(1, $doc->getPage());
    }

    public function testImageWebp(): void
    {
        $file = $this->getImagePath('webp');
        $doc = $this->createDocument();
        $doc->imageWebp($file);
        self::assertSame(1, $doc->getPage());
    }

    public function testImageXbm(): void
    {
        $file = $this->getImagePath('xbm');
        $doc = $this->createDocument();
        $doc->imageXbm($file);
        self::assertSame(1, $doc->getPage());
    }

    public function testImageXpm(): void
    {
        $file = $this->getImagePath('xpm');
        $doc = $this->createDocument();
        $doc->imageXpm($file);
        self::assertSame(1, $doc->getPage());
    }

    public function testInvalidLoader(): void
    {
        $doc = new class() extends PdfImageDocument {
            public function loadInvalidImage(): void
            {
                $this->imageFromLoader(
                    static fn (): false => false,
                    'fake'
                );
            }
        };
        self::expectException(PdfException::class);
        self::expectExceptionMessage('The image file "fake" is not a valid image.');
        $doc->loadInvalidImage();
    }

    private function createDocument(): PdfImageDocument
    {
        $doc = new PdfImageDocument();
        $doc->addPage();

        return $doc;
    }

    private function createGdImage(): \GdImage
    {
        $service = ImageService::fromTrueColor(200, 150);
        self::assertInstanceOf(ImageService::class, $service);

        $service->fill((int) $service->allocateWhite());
        $service->rectangle(0, 0, 199, 149, (int) $service->allocateBlack());
        $service->fillRectangle(30, 100, 30, 48, (int) $service->allocate(255, 0, 0));
        $service->fillRectangle(80, 80, 30, 68, (int) $service->allocate(0, 255, 0));
        $service->fillRectangle(130, 40, 30, 108, (int) $service->allocate(0, 0, 255));

        return $service->getImage();
    }

    private function getImagePath(string $extension): string
    {
        return \sprintf('%s/../../Data/images/example.%s', __DIR__, $extension);
    }
}
