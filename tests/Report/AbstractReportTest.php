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
use App\Pdf\PdfTable;
use App\Report\AbstractReport;
use PHPUnit\Framework\TestCase;

class AbstractReportTest extends TestCase
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
        $report->setDescriptionTrans('id');
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderCount(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $controller->method('getUserIdentifier')
            ->willReturn('user');
        $report = $this->createReport($controller);

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
        $report->setTitleTrans('id');
        $actual = $report->render();
        self::assertTrue($actual);
    }

    private function createReport(?AbstractController $controller = null): AbstractReport
    {
        $controller ??= $this->createMock(AbstractController::class);

        return new class($controller) extends AbstractReport {
            public function render(): bool
            {
                $this->translateCount(1);
                $this->translateCount([1, 2, 3]);

                return true;
            }
        };
    }
}
