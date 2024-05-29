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

namespace App\Tests\Pdf;

use App\Pdf\PdfAveryFormat;
use fpdf\PdfException;
use fpdf\PdfUnit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfAveryFormat::class)]
class PdfAveryFormatTest extends TestCase
{
    public function testInvalidFile(): void
    {
        self::expectException(PdfException::class);
        $file = __FILE__;
        PdfAveryFormat::loadFormats($file);
    }

    public function testLayoutInch(): void
    {
        $format = $this->getFormat('5164');
        self::assertSame(PdfUnit::INCH, $format->unit);
        $copy = $format->updateLayout();
        $expected = $format->marginLeft * 25.4;
        $actual = $copy->marginLeft;
        self::assertEqualsWithDelta($expected, $actual, 0.01);
    }

    public function testLayoutMillimeter(): void
    {
        $format = $this->getFormat('3422');
        self::assertSame(PdfUnit::MILLIMETER, $format->unit);
        $actual = $format->updateLayout();
        self::assertSame($format->marginLeft, $actual->marginLeft);
    }

    public function testNotExistFile(): void
    {
        self::expectException(PdfException::class);
        PdfAveryFormat::loadFormats('fake');
    }

    public function testOffsetX(): void
    {
        $format = $this->getFormat('5160');

        $actual = $format->getOffsetX(0);
        self::assertEqualsWithDelta(1.762, $actual, 0.01);

        $actual = $format->getOffsetX(1);
        self::assertEqualsWithDelta(1.762 + 3.175 + 66.675, $actual, 0.01);
    }

    public function testOffsetY(): void
    {
        $format = $this->getFormat('5160');

        $actual = $format->getOffsetY(0);
        self::assertEqualsWithDelta(10.7, $actual, 0.01);

        $actual = $format->getOffsetY(1);
        self::assertEqualsWithDelta(10.7 + 0.0 + 25.4, $actual, 0.01);
    }

    public function testSize(): void
    {
        $format = $this->getFormat('5160');
        $actual = $format->size();
        self::assertSame(30, $actual);
    }

    public function testValidWithDefaultFile(): void
    {
        $formats = PdfAveryFormat::loadFormats();
        self::assertNotEmpty($formats);
    }

    public function testValidWithFile(): void
    {
        $file = __DIR__ . '/../../resources/data/avery.json';
        $formats = PdfAveryFormat::loadFormats($file);
        self::assertNotEmpty($formats);
    }

    private function getFormat(string $name): PdfAveryFormat
    {
        $formats = PdfAveryFormat::loadFormats();

        return $formats[$name];
    }
}
