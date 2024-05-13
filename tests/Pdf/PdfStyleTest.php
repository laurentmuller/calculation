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

use App\Pdf\Colors\PdfDrawColor;
use App\Pdf\Colors\PdfFillColor;
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\PdfDocument;
use App\Pdf\PdfFont;
use App\Pdf\PdfLine;
use App\Pdf\PdfStyle;
use fpdf\PdfBorder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfStyle::class)]
class PdfStyleTest extends TestCase
{
    public function testApply(): void
    {
        $actual = new PdfStyle();
        $document = new PdfDocument();
        $document->addPage();
        $actual->apply($document);
        self::assertSame(1, $document->getPage());
    }

    public function testClone(): void
    {
        $actual = new PdfStyle();
        $clone = clone $actual;
        self::assertEqualsCanonicalizing($clone->getFont(), $actual->getFont());
        self::assertEqualsCanonicalizing($clone->getLine(), $actual->getLine());
        self::assertEqualsCanonicalizing($clone->getBorder(), $actual->getBorder());
        self::assertEqualsCanonicalizing($clone->getDrawColor(), $actual->getDrawColor());
        self::assertEqualsCanonicalizing($clone->getFillColor(), $actual->getFillColor());
    }

    public function testConstructor(): void
    {
        $actual = new PdfStyle();
        self::assertEqualsCanonicalizing(PdfFont::default(), $actual->getFont());
        self::assertEqualsCanonicalizing(PdfLine::default(), $actual->getLine());
        self::assertEqualsCanonicalizing(PdfBorder::all(), $actual->getBorder());
        self::assertEqualsCanonicalizing(PdfDrawColor::default(), $actual->getDrawColor());
        self::assertEqualsCanonicalizing(PdfFillColor::default(), $actual->getFillColor());
        self::assertEqualsCanonicalizing(PdfTextColor::default(), $actual->getTextColor());
    }
}
