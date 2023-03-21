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

use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfDocument;
use App\Pdf\PdfTableBuilder;
use PHPUnit\Framework\TestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(PdfTableBuilder::class)]
class PdfTableBuilderTest extends TestCase
{
    public function testAddCellNoRowStarted(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No row started.');
        $this->createTable()
            ->addCell(new PdfCell());
    }

    public function testAddCellsNoRowStarted(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No row started.');
        $this->createTable()
            ->addValues(new PdfCell());
    }

    public function testCompleteRowNoRowStarted(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No row started.');
        $this->createTable()
            ->completeRow();
    }

    public function testEndRowCellSpan(): void
    {
        $this->expectException(\OutOfRangeException::class);
        $this->expectExceptionMessage('Invalid spanned cells: expected 1, 2 given.');
        $this->createTable()
            ->addColumn(PdfColumn::left('', 25.0))
            ->startRow()
            ->addCell(new PdfCell(cols: 2))
            ->endRow();
    }

    public function testEndRowNoCell(): void
    {
        $this->expectException(\LengthException::class);
        $this->expectExceptionMessage('No cell defined.');
        $this->createTable()
            ->endRow();
    }

    public function testOutputHeadersNoColumn(): void
    {
        $this->expectException(\LengthException::class);
        $this->expectExceptionMessage('No column defined.');
        $this->createTable()
            ->outputHeaders();
    }

    public function testStartRowAlreadyStarted(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Row already started.');
        $this->createTable()
            ->startRow()
            ->startRow();
    }

    private function createTable(): PdfTableBuilder
    {
        $document = new PdfDocument();

        return new PdfTableBuilder($document);
    }
}
