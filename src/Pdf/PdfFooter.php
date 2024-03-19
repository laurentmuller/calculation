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

namespace App\Pdf;

use App\Report\AbstractReport;
use App\Utils\FormatUtils;
use fpdf\PdfBorder;
use fpdf\PdfTextAlignment;

/**
 * Class to output footer in PDF documents.
 *
 * The page and total pages are output to the left, the content (if any) to the center and the date and time to the right.
 */
class PdfFooter
{
    /**
     * The content text.
     */
    private ?string $content = null;

    /*
     * The formatted date.
     */
    private ?string $date = null;

    /**
     * The content URL.
     */
    private ?string $url = null;

    public function __construct(private readonly PdfDocument $parent)
    {
    }

    /**
     * Output this content to the parent document.
     */
    public function output(): void
    {
        $parent = $this->parent;
        $parent->useCellMargin(function () use ($parent): void {
            $parent->setY(-PdfDocument::FOOTER_OFFSET);
            $width = $parent->getPrintableWidth() / 3.0;
            PdfStyle::default()->setFontSize(8)->apply($parent);
            $this->outputText($this->getPage(), $width, PdfTextAlignment::LEFT)
                ->outputText($this->content ?? '', $width, PdfTextAlignment::CENTER, $this->url ?? '')
                ->outputText($this->getDate(), $width, PdfTextAlignment::RIGHT);
            $parent->resetStyle();
        });
    }

    /**
     * Sets the text content and the optional link.
     *
     * The given text (if any) is output to the center.
     */
    public function setContent(string $content, ?string $url = null): self
    {
        $this->content = $content;
        $this->url = $url;

        return $this;
    }

    /**
     * Gets the formatted current date.
     */
    private function getDate(): string
    {
        return $this->date ??= FormatUtils::formatDateTime(new \DateTime());
    }

    /**
     * Gets the formatted current and total pages.
     */
    private function getPage(): string
    {
        $parent = $this->parent;
        $page = $parent->getPage();
        if ($parent instanceof AbstractReport) {
            return $parent->trans('report.page', ['{0}' => $page, '{1}' => '{nb}']);
        }

        return "Page $page / {nb}";
    }

    /**
     * Output the given text.
     */
    private function outputText(string $text, float $width, PdfTextAlignment $align, string $link = ''): self
    {
        $this->parent->cell(
            width: $width,
            text: $text,
            border: PdfBorder::top(),
            align: $align,
            link: $link
        );

        return $this;
    }
}
