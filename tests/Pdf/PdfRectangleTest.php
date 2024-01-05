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

use App\Pdf\PdfRectangle;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(PdfRectangle::class)]
class PdfRectangleTest extends TestCase
{
    public function testBottom(): void
    {
        $expected = 30.0;
        $r = new PdfRectangle(10, 10, 20, 20);
        $actual = $r->bottom();
        self::assertSame($expected, $actual);
    }

    public function testContains(): void
    {
        $actual = new PdfRectangle(10, 10, 20, 20);
        self::assertTrue($actual->contains(10, 10));
        self::assertTrue($actual->contains(15, 15));
        self::assertTrue($actual->contains(29.999999, 29.999999));

        self::assertFalse($actual->contains(0, 0));
        self::assertFalse($actual->contains(30, 30));
    }

    public function testIndent(): void
    {
        $actual = new PdfRectangle(10, 10, 20, 20);
        $actual->indent(5);
        self::assertValidBounds($actual, 15, 10, 15, 20);
    }

    public function testIndentNegative(): void
    {
        $actual = new PdfRectangle(10, 10, 20, 20);
        $actual->indent(-10);
        self::assertValidBounds($actual, 10, 10, 20, 20);
    }

    public function testIndentZero(): void
    {
        $actual = new PdfRectangle(10, 10, 20, 20);
        $actual->indent(0);
        self::assertValidBounds($actual, 10, 10, 20, 20);
    }

    public function testInflate(): void
    {
        $actual = new PdfRectangle(0, 0, 10, 10);
        $actual->inflate(5);
        self::assertValidBounds($actual, -5, -5, 20, 20);
    }

    public function testInflateX(): void
    {
        $actual = new PdfRectangle(0, 0, 10, 10);
        $actual->inflateX(5);
        self::assertValidBounds($actual, -5, 0, 20, 10);
    }

    public function testInflateXY(): void
    {
        $actual = new PdfRectangle(0, 0, 10, 10);
        $actual->inflateXY(5, 5);
        self::assertValidBounds($actual, -5, -5, 20, 20);
    }

    public function testInflateY(): void
    {
        $actual = new PdfRectangle(0, 0, 10, 10);
        $actual->inflateY(5);
        self::assertValidBounds($actual, 0, -5, 10, 20);
    }

    public function testIntersect(): void
    {
        $r1 = new PdfRectangle(10, 10, 20, 20);
        $r2 = new PdfRectangle(10, 10, 20, 20);
        self::assertTrue($r1->intersect($r2));
    }

    public function testRight(): void
    {
        $expected = 30.0;
        $r = new PdfRectangle(10, 10, 20, 20);
        $actual = $r->right();
        self::assertSame($expected, $actual);
    }

    public function testSetBottom(): void
    {
        $actual = new PdfRectangle(10, 10, 20, 20);
        $actual->setBottom(40);
        self::assertValidBounds($actual, 10, 10, 20, 30);
    }

    public function testSetRight(): void
    {
        $actual = new PdfRectangle(10, 10, 20, 20);
        $actual->setRight(40);
        self::assertValidBounds($actual, 10, 10, 30, 20);
    }

    public function testSetSize(): void
    {
        $actual = new PdfRectangle(0, 0, 20, 20);
        $actual->setSize(10, 10);
        self::assertValidBounds($actual, 0, 0, 10, 10);
    }

    public function testUnion(): void
    {
        $r1 = new PdfRectangle(0, 0, 20, 20);
        $r2 = new PdfRectangle(10, 10, 20, 20);
        $actual = $r1->union($r2);
        self::assertValidBounds($actual, 0, 0, 30, 30);
    }

    public function testX(): void
    {
        $expected = 30.0;
        $r = new PdfRectangle(30, 10, 20, 20);
        $actual = $r->x();
        self::assertSame($expected, $actual);
    }

    public function testY(): void
    {
        $expected = 30.0;
        $r = new PdfRectangle(30, 30, 20, 20);
        $actual = $r->y();
        self::assertSame($expected, $actual);
    }

    protected static function assertValidBounds(PdfRectangle $actual, float $x, float $y, float $w, float $h): void
    {
        self::assertSame($x, $actual->x());
        self::assertSame($y, $actual->y());
        self::assertSame($w, $actual->width());
        self::assertSame($h, $actual->height());
    }
}
