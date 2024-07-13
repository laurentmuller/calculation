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
use fpdf\PdfBorder;
use fpdf\PdfTextAlignment;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class HtmlReportOutputTest extends TestCase
{
    use TranslatorMockTrait;

    /**
     * @throws Exception
     */
    public function testHtmlLiChunkNoParent(): void
    {
        $report = $this->createReport();
        $chunk = new HtmlLiChunk('li');
        $chunk->output($report);
        self::assertSame(1, $report->getPage());
    }

    /**
     * @throws Exception
     */
    public function testHtmlLiChunkWithStyle(): void
    {
        $report = $this->createReport();
        $textChunk = new HtmlTextChunk('#text');
        $textChunk->setText('Text');
        $textChunk->setStyle(HtmlStyle::default()->setFontBold());
        $liChunk = new HtmlLiChunk('li');
        $liChunk->add($textChunk);
        $parent = new HtmlOlChunk('ol');
        $parent->add($liChunk);
        $parent->setStyle(HtmlStyle::default()->setLeftMargin(10)
            ->setRightMargin(10));
        $parent->output($report);
        self::assertSame(1, $report->getPage());
    }

    /**
     * @throws Exception
     */
    public function testHtmlOlChunk(): void
    {
        $report = $this->createReport();
        $chunk = new HtmlOlChunk('ol');
        $chunk->add(new HtmlLiChunk('li'));
        $chunk->add(new HtmlLiChunk('li'));
        $chunk->output($report);
        self::assertSame(1, $report->getPage());
    }

    /**
     * @throws Exception
     */
    public function testHtmlPageBreakChunk(): void
    {
        $report = $this->createReport();
        $chunk = new HtmlPageBreakChunk('tag');
        $chunk->output($report);
        self::assertSame(2, $report->getPage());
    }

    /**
     * @throws Exception
     */
    public function testHtmlTextChunk(): void
    {
        $report = $this->createReport();
        $chunk = new HtmlTextChunk('#text');
        $chunk->setText('Text');
        $chunk->output($report);
        self::assertSame(1, $report->getPage());

        $style = HtmlStyle::default();
        $style->setAlignment(PdfTextAlignment::RIGHT);
        $parent = new HtmlParentChunk('h1', null, 'bookmark-0');
        $parent->setStyle($style);
        $chunk = new HtmlTextChunk('#text');
        $chunk->setText('Text');
        $parent->add($chunk);
        $parent->output($report);
        self::assertSame(1, $report->getPage());
    }

    /**
     * @throws Exception
     */
    public function testHtmlUlChunk(): void
    {
        $report = $this->createReport();
        $chunk = new HtmlUlChunk('ol');
        $chunk->add(new HtmlLiChunk('li'));
        $chunk->add(new HtmlLiChunk('li'));
        $chunk->output($report);
        self::assertSame(1, $report->getPage());
    }

    /**
     * @throws Exception
     */
    public function testOuputWithBorder(): void
    {
        $report = $this->createReport();
        $chunk = new HtmlParentChunk('div');
        $chunk->setStyle(HtmlStyle::default()->setBorder(PdfBorder::all()));
        $text = new HtmlTextChunk(HtmlTag::TEXT->value);
        $text->setText('Text');
        $chunk->add($text);
        $chunk->output($report);

        $report->setX(200);
        $text->setText('Very long text to use multi-cell function in the report.');
        $chunk->output($report);

        self::assertSame(2, $report->getPage());
    }

    /**
     * @throws Exception
     */
    private function createReport(): HtmlReport
    {
        $controller = $this->createMock(AbstractController::class);
        $controller->method('getTranslator')
            ->willReturn($this->createMockTranslator());

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
