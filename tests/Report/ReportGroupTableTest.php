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

namespace App\Tests\Report;

use App\Controller\AbstractController;
use App\Pdf\PdfColumn;
use App\Pdf\PdfDocument;
use App\Pdf\Traits\PdfCellTranslatorTrait;
use App\Report\AbstractReport;
use App\Report\Table\ReportGroupTable;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

#[CoversClass(ReportGroupTable::class)]
#[CoversClass(PdfCellTranslatorTrait::class)]
class ReportGroupTableTest extends TestCase
{
    use TranslatorMockTrait;

    public function testAddCellTrans(): void
    {
        $document = new PdfDocument();
        $document->resetStyle()
            ->addPage();
        $table = new ReportGroupTable($document, $this->createMockTranslator());
        $table->addColumns(PdfColumn::left('', 10.0));
        $table->startRow()
            ->addCellTrans('id')
            ->endRow();
        self::assertSame(1, $document->getPage());
    }

    /**
     * @throws Exception
     */
    public function testFromReport(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $report = new class($controller) extends AbstractReport {
            public function render(): bool
            {
                return true;
            }
        };
        $table = ReportGroupTable::fromReport($report);
        self::assertSame(0, $table->getColumnsCount());
    }

    public function testRender(): void
    {
        $document = new PdfDocument();
        $table = new ReportGroupTable($document, $this->createMockTranslator());
        $table->addColumns(PdfColumn::left('', 10.0));
        self::assertSame(0, $document->getPage());
        self::assertInstanceOf(TranslatorInterface::class, $table->getTranslator());
    }
}
