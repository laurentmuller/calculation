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

use App\Pdf\PdfColumn;
use fpdf\Enums\PdfTextAlignment;
use PHPUnit\Framework\TestCase;

class PdfColumnTest extends TestCase
{
    public function testAlignment(): void
    {
        $actual = new PdfColumn(null);
        self::assertSame(PdfTextAlignment::LEFT, $actual->getAlignment());
        $actual->setAlignment(PdfTextAlignment::RIGHT);
        self::assertSame(PdfTextAlignment::RIGHT, $actual->getAlignment());
    }

    public function testCenter(): void
    {
        $actual = PdfColumn::center(null, 0.0);
        self::assertSame(PdfTextAlignment::CENTER, $actual->getAlignment());
    }

    public function testConstructor(): void
    {
        $actual = new PdfColumn(null);
        self::assertNull($actual->getText());
        self::assertSame(0.0, $actual->getWidth());
        self::assertSame(PdfTextAlignment::LEFT, $actual->getAlignment());
        self::assertFalse($actual->isFixed());
    }

    public function testFixed(): void
    {
        $actual = new PdfColumn(null);
        self::assertFalse($actual->isFixed());
        $actual->setFixed(true);
        self::assertTrue($actual->isFixed());
    }

    public function testLeft(): void
    {
        $actual = PdfColumn::left(null, 0.0);
        self::assertSame(PdfTextAlignment::LEFT, $actual->getAlignment());
    }

    public function testRight(): void
    {
        $actual = PdfColumn::right(null, 0.0);
        self::assertSame(PdfTextAlignment::RIGHT, $actual->getAlignment());
    }

    public function testText(): void
    {
        $actual = new PdfColumn(null);
        self::assertNull($actual->getText());
        $actual->setText('text');
        self::assertSame('text', $actual->getText());
    }

    public function testWidth(): void
    {
        $actual = new PdfColumn(null);
        self::assertSame(0.0, $actual->getWidth());
        $actual->setWidth(10.0);
        self::assertSame(10.0, $actual->getWidth());
    }
}
