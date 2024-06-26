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
use App\Pdf\Traits\PdfTransparencyTrait;
use fpdf\PdfDestination;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfTransparencyTrait::class)]
class PdfTransparencyTraitTest extends TestCase
{
    public function testRender(): void
    {
        $document = new class() extends PdfDocument {
            use PdfTransparencyTrait;
        };
        $document->addPage();
        $document->setAlpha(0.5);
        $document->resetAlpha();
        $document->output(PdfDestination::STRING);
        self::assertSame(1, $document->getPage());
    }
}
