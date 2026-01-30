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

namespace App\Tests\Pdf\Html;

use App\Controller\AbstractController;
use App\Pdf\Html\HtmlLiChunk;
use App\Pdf\Html\HtmlOlChunk;
use App\Pdf\Html\HtmlPageBreakChunk;
use App\Pdf\Html\HtmlParentChunk;
use App\Pdf\Html\HtmlStyle;
use App\Pdf\Html\HtmlTag;
use App\Pdf\Html\HtmlTextChunk;
use App\Pdf\Html\HtmlUlChunk;
use App\Report\HtmlReport;
use App\Tests\TranslatorMockTrait;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfBorder;
use PHPUnit\Framework\TestCase;

final class HtmlReportOutputTest extends TestCase
{
    use TranslatorMockTrait;

    public function testHtmlLiChunkNoParent(): void
    {
        $report = $this->createReport();
        $chunk = new HtmlLiChunk();
        $chunk->output($report);
        self::assertSame(1, $report->getPage());
    }

    public function testHtmlLiChunkWithStyle(): void
    {
        $report = $this->createReport();
        $textChunk = new HtmlTextChunk(text: 'Text');
        $textChunk->setStyle(HtmlStyle::default()->setFontBold());
        $liChunk = new HtmlLiChunk();
        $liChunk->add($textChunk);
        $parent = new HtmlOlChunk();
        $parent->add($liChunk);
        $parent->setStyle(HtmlStyle::default()->setLeftMargin(10)
            ->setRightMargin(10));
        $parent->output($report);
        self::assertSame(1, $report->getPage());
    }

    public function testHtmlOlChunk(): void
    {
        $report = $this->createReport();
        $chunk = new HtmlOlChunk();
        $chunk->add(new HtmlLiChunk());
        $chunk->add(new HtmlLiChunk());
        $chunk->output($report);
        self::assertSame(1, $report->getPage());
    }

    public function testHtmlPageBreakChunk(): void
    {
        $report = $this->createReport();
        $chunk = new HtmlPageBreakChunk();
        $chunk->output($report);
        self::assertSame(2, $report->getPage());
    }

    public function testHtmlTextChunk(): void
    {
        $report = $this->createReport();
        $chunk = new HtmlTextChunk(text: 'Text');
        $chunk->output($report);
        self::assertSame(1, $report->getPage());

        $style = HtmlStyle::default();
        $style->setAlignment(PdfTextAlignment::RIGHT);
        $parent = new HtmlParentChunk(tag: HtmlTag::H1, className: 'bookmark-0');
        $parent->setStyle($style);
        $chunk = new HtmlTextChunk(text: 'Text');
        $parent->add($chunk);
        $parent->output($report);
        self::assertSame(1, $report->getPage());
    }

    public function testHtmlUlChunk(): void
    {
        $report = $this->createReport();
        $chunk = new HtmlUlChunk();
        $chunk->add(new HtmlLiChunk());
        $chunk->add(new HtmlLiChunk());
        $chunk->output($report);
        self::assertSame(1, $report->getPage());
    }

    public function testOutputWithBorder(): void
    {
        $report = $this->createReport();
        $chunk = new HtmlParentChunk(HtmlTag::PARAGRAPH);
        $chunk->setStyle(HtmlStyle::default()->setBorder(PdfBorder::all()));
        $text = new HtmlTextChunk(text: 'Text');
        $chunk->add($text);
        $chunk->output($report);

        $report->setX(200);
        $text = new HtmlTextChunk(text: 'Very long text to use multi-cell function in the report.');
        $chunk->add($text);
        $chunk->output($report);

        self::assertSame(2, $report->getPage());
    }

    private function createReport(): HtmlReport
    {
        $translator = $this->createMockTranslator();
        $controller = $this->createMock(AbstractController::class);
        $controller->method('getTranslator')
            ->willReturn($translator);

        $html = <<<HTML
                <!DOCTYPE html>
                <html>
                    <head>
                    <title>Title of the document</title>
                    </head>
                    <body>
                        <p>The content of the document.</p>
                    </body>
                </html>
            HTML;

        $report = new HtmlReport(
            $controller,
            $html
        );
        $report->resetStyle()
            ->addPage();

        return $report;
    }
}
