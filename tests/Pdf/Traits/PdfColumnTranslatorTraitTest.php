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

use App\Controller\AbstractController;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Report\AbstractReport;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class PdfColumnTranslatorTraitTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testColumns(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $report = new class($controller) extends AbstractReport {
            public function render(): bool
            {
                $this->addPage();
                PdfStyle::default()->apply($this);
                $table = PdfTable::instance($this)
                    ->addColumns(
                        $this->leftColumn('left', 20, true),
                        $this->centerColumn('center', 17, true),
                        $this->rightColumn('right', 70),
                    )->outputHeaders();
                $table->startRow()
                    ->addValues('left', 'center', 'right')
                    ->endRow();

                return true;
            }
        };
        $actual = $report->render();
        self::assertTrue($actual);
    }
}
