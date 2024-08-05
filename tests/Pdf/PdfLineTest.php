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

use App\Pdf\PdfLine;
use fpdf\PdfDocument;
use PHPUnit\Framework\TestCase;

class PdfLineTest extends TestCase
{
    public function testApply(): void
    {
        $doc = new PdfDocument();
        $actual = new PdfLine(3.0);
        $actual->apply($doc);
        self::assertSame(3.0, $doc->getLineWidth());
    }

    public function testConstructor(): void
    {
        $actual = new PdfLine();
        self::assertSame(0.2, $actual->getWidth());
    }

    public function testCreate(): void
    {
        $actual = PdfLine::create();
        self::assertSame(0.2, $actual->getWidth());

        $actual = PdfLine::create(3.0);
        self::assertSame(3.0, $actual->getWidth());
    }

    public function testDefault(): void
    {
        $actual = PdfLine::default();
        self::assertSame(0.2, $actual->getWidth());
    }

    public function testSetWidth(): void
    {
        $actual = PdfLine::default();
        self::assertSame(0.2, $actual->getWidth());
        $actual->setWidth(3.0);
        self::assertSame(3.0, $actual->getWidth());
    }
}
