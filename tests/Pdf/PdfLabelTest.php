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
use fpdf\Enums\PdfUnit;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class PdfLabelTest extends TestCase
{
    private PdfLabelService $service;

    #[\Override]
    protected function setUp(): void
    {
        $this->service = new PdfLabelService(new ArrayAdapter());
    }

    public function testOffsetX(): void
    {
        $label = $this->getLabel('5160');
        $actual = $label->getOffsetX(0);
        self::assertEqualsWithDelta(1.762, $actual, 0.01);
        $actual = $label->getOffsetX(1);
        self::assertEqualsWithDelta(1.762 + 3.175 + 66.675, $actual, 0.01);
    }

    public function testOffsetY(): void
    {
        $label = $this->getLabel('5160');
        $actual = $label->getOffsetY(0);
        self::assertEqualsWithDelta(10.7, $actual, 0.01);
        $actual = $label->getOffsetY(1);
        self::assertEqualsWithDelta(10.7 + 0.0 + 25.4, $actual, 0.01);
    }

    public function testScaleFromInch(): void
    {
        $label = $this->getLabel('5164');
        self::assertSame(PdfUnit::INCH, $label->unit);
        $copy = $label->scaleToMillimeters();
        $expected = $label->marginLeft * 25.4;
        $actual = $copy->marginLeft;
        self::assertEqualsWithDelta($expected, $actual, 0.01);
    }

    public function testScaleFromMillimeter(): void
    {
        $label = $this->getLabel('3422');
        self::assertSame(PdfUnit::MILLIMETER, $label->unit);
        $actual = $label->scaleToMillimeters();
        self::assertSame($label->marginLeft, $actual->marginLeft);
    }

    public function testSize(): void
    {
        $label = $this->getLabel('5160');
        $actual = $label->size();
        self::assertSame(30, $actual);
    }

    private function getLabel(string $name): PdfLabel
    {
        return $this->service->get($name);
    }
}
