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

use App\Pdf\PdfLabel;
use App\Service\PdfLabelService;
use fpdf\PdfUnit;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

#[CoversClass(PdfLabel::class)]
class PdfLabelTest extends TestCase
{
    public function testOffsetX(): void
    {
        $format = $this->getLabel('5160');

        $actual = $format->getOffsetX(0);
        self::assertEqualsWithDelta(1.762, $actual, 0.01);

        $actual = $format->getOffsetX(1);
        self::assertEqualsWithDelta(1.762 + 3.175 + 66.675, $actual, 0.01);
    }

    public function testOffsetY(): void
    {
        $format = $this->getLabel('5160');

        $actual = $format->getOffsetY(0);
        self::assertEqualsWithDelta(10.7, $actual, 0.01);

        $actual = $format->getOffsetY(1);
        self::assertEqualsWithDelta(10.7 + 0.0 + 25.4, $actual, 0.01);
    }

    public function testScaleFromInch(): void
    {
        $format = $this->getLabel('5164');
        self::assertSame(PdfUnit::INCH, $format->unit);
        $copy = $format->scaleToMillimeters();
        $expected = $format->marginLeft * 25.4;
        $actual = $copy->marginLeft;
        self::assertEqualsWithDelta($expected, $actual, 0.01);
    }

    public function testScaleFromMillimeter(): void
    {
        $format = $this->getLabel('3422');
        self::assertSame(PdfUnit::MILLIMETER, $format->unit);
        $actual = $format->scaleToMillimeters();
        self::assertSame($format->marginLeft, $actual->marginLeft);
    }

    public function testSize(): void
    {
        $format = $this->getLabel('5160');
        $actual = $format->size();
        self::assertSame(30, $actual);
    }

    private function getLabel(string $name): PdfLabel
    {
        $service = new PdfLabelService(new ArrayAdapter());

        return $service->get($name);
    }
}
