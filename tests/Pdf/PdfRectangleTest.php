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

/**
 * Unit test for {@link PdfRectangle} class.
 */
class PdfRectangleTest extends TestCase
{
    public function testBottom(): void
    {
        $r = new PdfRectangle(10, 10, 20, 20);
        self::assertSame(30.0, $r->bottom());
    }

    public function testContains(): void
    {
        $r = new PdfRectangle(10, 10, 20, 20);
        self::assertTrue($r->contains(10, 10));
        self::assertTrue($r->contains(15, 15));
        self::assertTrue($r->contains(29.999999, 29.999999));

        self::assertFalse($r->contains(0, 0));
        self::assertFalse($r->contains(30, 30));
    }

    public function testIndent(): void
    {
        $r = new PdfRectangle(10, 10, 20, 20);
        $r->indent(5);
        $this->validate($r, 15, 10, 15, 20);
    }

    public function testIndentNegative(): void
    {
        $r = new PdfRectangle(10, 10, 20, 20);
        $r->indent(-10);
        $this->validate($r, 10, 10, 20, 20);
    }

    public function testIndentZero(): void
    {
        $r = new PdfRectangle(10, 10, 20, 20);
        $r->indent(0);
        $this->validate($r, 10, 10, 20, 20);
    }

    public function testInflate(): void
    {
        $r = new PdfRectangle(0, 0, 10, 10);
        $r->inflate(5);
        $this->validate($r, -5, -5, 20, 20);
    }

    public function testInflateX(): void
    {
        $r = new PdfRectangle(0, 0, 10, 10);
        $r->inflateX(5);
        $this->validate($r, -5, 0, 20, 10);
    }

    public function testInflateXY(): void
    {
        $r = new PdfRectangle(0, 0, 10, 10);
        $r->inflateXY(5, 5);
        $this->validate($r, -5, -5, 20, 20);
    }

    public function testInflateY(): void
    {
        $r = new PdfRectangle(0, 0, 10, 10);
        $r->inflateY(5);
        $this->validate($r, 0, -5, 10, 20);
    }

    public function testIntersect(): void
    {
        $r1 = new PdfRectangle(10, 10, 20, 20);
        $r2 = new PdfRectangle(10, 10, 20, 20);
        self::assertTrue($r1->intersect($r2));
    }

    public function testRight(): void
    {
        $r = new PdfRectangle(10, 10, 20, 20);
        self::assertSame(30.0, $r->right());
    }

    public function testSetBottom(): void
    {
        $r = new PdfRectangle(10, 10, 20, 20);
        $r->setBottom(40);
        $this->validate($r, 10, 10, 20, 30);
    }

    public function testSetRight(): void
    {
        $r = new PdfRectangle(10, 10, 20, 20);
        $r->setRight(40);
        $this->validate($r, 10, 10, 30, 20);
    }

    public function testSetSize(): void
    {
        $r = new PdfRectangle(0, 0, 20, 20);
        $r->setSize(10, 10);
        $this->validate($r, 0, 0, 10, 10);
    }

    public function testUnion(): void
    {
        $r1 = new PdfRectangle(0, 0, 20, 20);
        $r2 = new PdfRectangle(10, 10, 20, 20);
        $r3 = $r1->union($r2);
        $this->validate($r3, 0, 0, 30, 30);
    }

    private function validate(PdfRectangle $r, float $x, float $y, float $w, float $h): void
    {
        self::assertSame($x, $r->x());
        self::assertSame($y, $r->y());
        self::assertSame($w, $r->width());
        self::assertSame($h, $r->height());
    }
}
