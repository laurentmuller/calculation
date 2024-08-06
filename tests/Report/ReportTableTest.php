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
use App\Report\Table\ReportTable;
use App\Tests\Data\TestReport;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ReportTableTest extends TestCase
{
    use TranslatorMockTrait;

    private MockObject&TranslatorInterface $translator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->translator = $this->createMockTranslator();
    }

    /**
     * @throws Exception
     */
    public function testAddCellTrans(): void
    {
        $report = $this->createReport();
        $report->resetStyle()
            ->addPage();
        $table = new ReportTable($report);
        $table->addColumns(PdfColumn::left('', 10.0));
        $table->startRow()
            ->addCellTrans('id')
            ->endRow();
        self::assertSame(1, $report->getPage());
    }

    /**
     * @throws Exception
     */
    public function testFromReport(): void
    {
        $report = $this->createReport();
        $table = ReportTable::fromReport($report);
        self::assertSame(0, $table->getColumnsCount());
    }

    /**
     * @throws Exception
     */
    public function testRender(): void
    {
        $report = $this->createReport();
        $table = ReportTable::fromReport($report);
        $table->addColumns(PdfColumn::left('', 10.0));
        self::assertSame(0, $report->getPage());
        self::assertInstanceOf(TranslatorInterface::class, $table->getTranslator());
    }

    /**
     * @throws Exception
     */
    private function createReport(): TestReport
    {
        $controller = $this->createMock(AbstractController::class);
        $controller->method('getTranslator')
            ->willReturn($this->translator);

        return new TestReport($controller);
    }
}
