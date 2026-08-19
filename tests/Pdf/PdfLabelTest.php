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
        $this->service = new PdfLabelService(
            file: __DIR__ . '/../../resources/data/labels.json',
            cache: new ArrayAdapter()
        );
    }

    public function testFromArray(): void
    {
        $source = [
            'name' => '3422',
            'cols' => 3,
            'rows' => 8,
            'width' => 70,
            'height' => 35,
            'marginLeft' => 0,
            'marginTop' => 8.5,
            'spaceWidth' => 0,
            'spaceHeight' => 0,
            'fontSize' => 9,
            'unit' => 'mm',
            'pageSize' => 'A4',
        ];
        $label = PdfLabel::fromArray($source);
        self::assertSame('3422', $label->name);
    }

    public function testFromArrayInvalidEnum(): void
    {
        $source = [
            'name' => '3422',
            'cols' => 3,
            'rows' => 8,
            'width' => 70,
            'height' => 35,
            'marginLeft' => 0,
            'marginTop' => 8.5,
            'spaceWidth' => 0,
            'spaceHeight' => 0,
            'fontSize' => 9,
            'unit' => 'fake',
            'pageSize' => 'A4',
        ];
        self::expectException(\ValueError::class);
        self::expectExceptionMessage('"fake" is not a valid backing value for enum fpdf\Enums\PdfUnit');
        PdfLabel::fromArray($source);
    }

    public function testFromArrayInvalidValue(): void
    {
        $source = [
            'name' => '3422',
            'cols' => 3,
            'rows' => 8,
            'width' => 70,
            'height' => 35,
            'marginLeft' => 'fake',
            'marginTop' => 8.5,
            'spaceWidth' => 0,
            'spaceHeight' => 0,
            'fontSize' => 9,
            'unit' => 'mm',
            'pageSize' => 'A4',
        ];
        self::expectException(\TypeError::class);
        self::expectExceptionMessageMatches('/\\(\\$marginLeft\\) must be of type float, string given/');
        PdfLabel::fromArray($source);
    }

    public function testOffsetX(): void
    {
        $label = $this->getLabel('5160');
        $actual = $label->offsetX(0);
        self::assertEqualsWithDelta(1.762, $actual, 0.01);
        $actual = $label->offsetX(1);
        self::assertEqualsWithDelta(1.762 + 3.175 + 66.675, $actual, 0.01);
    }

    public function testOffsetY(): void
    {
        $label = $this->getLabel('5160');
        $actual = $label->offsetY(0);
        self::assertEqualsWithDelta(10.7, $actual, 0.01);
        $actual = $label->offsetY(1);
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

    public function testToString(): void
    {
        $label = $this->getLabel('5160');
        $actual = (string) $label;
        self::assertSame('5160', $actual);
    }

    private function getLabel(string $name): PdfLabel
    {
        return $this->service->get($name);
    }
}
