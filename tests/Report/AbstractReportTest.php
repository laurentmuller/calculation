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

use App\Interfaces\DocumentHelperInterface;
use App\Pdf\PdfColumn;
use App\Pdf\PdfTable;
use App\Report\AbstractReport;
use PHPUnit\Framework\TestCase;

final class AbstractReportTest extends TestCase
{
    public function testAddPageIndex(): void
    {
        $report = $this->createReport();
        $report->addPageIndex();
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testDescriptionTrans(): void
    {
        $report = $this->createReport();
        $report->setTranslatedDescription('id');
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderCount(): void
    {
        $helper = $this->createMock(DocumentHelperInterface::class);
        $helper->method('getUserIdentifier')
            ->willReturn('user');
        $report = $this->createReport($helper);

        $report->addPage();
        $table = new PdfTable($report);
        $table->addColumns(PdfColumn::left(width: 10.0));
        $report->renderCount($table, 1);
        $report->renderCount($table, []);
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testTitleTrans(): void
    {
        $report = $this->createReport();
        $report->setTranslatedTitle('id');
        $actual = $report->render();
        self::assertTrue($actual);
    }

    private function createReport(?DocumentHelperInterface $helper = null): AbstractReport
    {
        $helper ??= $this->createMock(DocumentHelperInterface::class);

        return new class($helper) extends AbstractReport {
            #[\Override]
            public function render(): bool
            {
                $this->translateCount(1);
                $this->translateCount([1, 2, 3]);

                return true;
            }
        };
    }
}
