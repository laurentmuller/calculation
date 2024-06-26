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
use App\Pdf\Traits\PdfSectorTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfSectorTrait::class)]
class PdfSectorTraitTest extends TestCase
{
    public function testEmpty(): void
    {
        $document = new class() extends PdfDocument {
            use PdfSectorTrait;
        };
        $document->sector(100, 100, 0, 0, 90);
        self::assertSame(0, $document->getPage());
    }

    public function testNegativeAngle(): void
    {
        $document = new class() extends PdfDocument {
            use PdfSectorTrait;
        };
        $document->addPage();
        $document->sector(100, 100, 50, -10, 90);
        self::assertSame(1, $document->getPage());
    }

    public function testRenderClockwise(): void
    {
        $document = new class() extends PdfDocument {
            use PdfSectorTrait;
        };
        $document->addPage();
        $document->sector(100, 100, 50, 0, 90);
        self::assertSame(1, $document->getPage());
    }

    public function testRenderCounterClockwise(): void
    {
        $document = new class() extends PdfDocument {
            use PdfSectorTrait;
        };
        $document->addPage();
        $document->sector(100, 100, 50, 0, 90, clockwise: false);
        self::assertSame(1, $document->getPage());
    }
}
