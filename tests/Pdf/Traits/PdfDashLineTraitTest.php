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

use App\Pdf\PdfDocument;
use App\Pdf\PdfLine;
use App\Pdf\Traits\PdfDashLineTrait;
use fpdf\PdfDestination;
use fpdf\PdfRectangle;
use PHPUnit\Framework\TestCase;

class PdfDashLineTraitTest extends TestCase
{
    public function testRender(): void
    {
        $document = new class() extends PdfDocument {
            use PdfDashLineTrait;
        };
        $document->addPage();
        $document->dashedRect(10, 10, 100, 50, 5);
        $document->dashedRect(10, 10, 100, 50, 5, 0.25);
        $document->dashedRect(10, 10, 100, 50, 5, PdfLine::default());
        $document->dashedRectangle(PdfRectangle::instance(10, 10, 100, 50));
        $document->setDashPattern(10, 5);
        $document->setDashPattern();
        $document->output(PdfDestination::STRING);
        self::assertSame(1, $document->getPage());
    }
}
