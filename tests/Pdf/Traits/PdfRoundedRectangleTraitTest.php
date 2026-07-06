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
use App\Pdf\Traits\PdfRoundedRectangleTrait;
use App\Report\AbstractReport;
use fpdf\Color\PdfRgbColor;
use fpdf\Enums\PdfDestination;
use fpdf\PdfException;
use fpdf\PdfRectangle;
use PHPUnit\Framework\TestCase;

final class PdfRoundedRectangleTraitTest extends TestCase
{
    public function testRoundedInvalidRadius(): void
    {
        $controller = self::createStub(AbstractController::class);
        $report = new class($controller) extends AbstractReport {
            use PdfRoundedRectangleTrait;

            #[\Override]
            public function render(): bool
            {
                return true;
            }
        };
        self::expectException(PdfException::class);
        self::expectExceptionMessage('Invalid radius: 40, maximum allowed: 25.');
        $report->roundedRect(0, 0, 50, 100, 40);
    }

    public function testRoundedRadiusZero(): void
    {
        $controller = self::createStub(AbstractController::class);
        $report = new class($controller) extends AbstractReport {
            use PdfRoundedRectangleTrait;

            #[\Override]
            public function render(): bool
            {
                return true;
            }
        };
        self::expectException(PdfException::class);
        self::expectExceptionMessage('The radius must be positive, 0 given.');
        $report->roundedRect(0, 0, 50, 100, 0);
    }

    public function testRoundedRect(): void
    {
        $controller = self::createStub(AbstractController::class);
        $report = new class($controller) extends AbstractReport {
            use PdfRoundedRectangleTrait;

            #[\Override]
            public function render(): bool
            {
                return true;
            }
        };
        $report->addPage();
        $report->setFillColor(PdfRgbColor::darkGray());
        $report->setDrawColor(PdfRgbColor::red());

        $report->setLineWidth(1.5);
        $report->roundedRect(10, 20, 20, 20, 5);
        $report->setLineWidth(0.5);
        $report->roundedRect(10, 80, 100, 10, 5);
        self::assertTrue($report->render());

        $report->output(
            PdfDestination::FILE,
            'd:/temp/rounded-rect.pdf',
        );
    }

    public function testRoundedRectangle(): void
    {
        $rect = new PdfRectangle(10, 20, 100, 50);
        $controller = self::createStub(AbstractController::class);
        $report = new class($controller) extends AbstractReport {
            use PdfRoundedRectangleTrait;

            #[\Override]
            public function render(): bool
            {
                return true;
            }
        };
        $report->addPage();
        $report->setFillColor(PdfRgbColor::darkGray());
        $report->setDrawColor(PdfRgbColor::red());
        $report->setLineWidth(1.5);
        $report->roundedRectangle($rect, 5);
        self::assertTrue($report->render());
    }
}
