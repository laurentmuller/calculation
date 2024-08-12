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

use App\Tests\Data\PdfInvalidColor;
use fpdf\PdfException;
use PHPUnit\Framework\TestCase;

class PdfInvalidColorTest extends TestCase
{
    public function testInvalidDrawColor(): void
    {
        self::expectException(PdfException::class);
        self::expectExceptionMessage('Unable to create draw color.');
        $this->getPdfInvalidColor()
            ->getDrawColor();
    }

    public function testInvalidFillColor(): void
    {
        self::expectException(PdfException::class);
        self::expectExceptionMessage('Unable to create fill color.');
        $this->getPdfInvalidColor()
            ->getFillColor();
    }

    public function testInvalidTextColor(): void
    {
        self::expectException(PdfException::class);
        self::expectExceptionMessage('Unable to create text color.');
        $this->getPdfInvalidColor()
            ->getTextColor();
    }

    private function getPdfInvalidColor(): PdfInvalidColor
    {
        return new PdfInvalidColor();
    }
}
