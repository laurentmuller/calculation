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

use App\Pdf\Enums\PdfTextAlignment;
use App\Report\AbstractReport;
use App\Utils\FormatUtils;

/**
 * Class to output footer in PDF documents.
 *
 * The page and total pages are output to the left, the content (if any) to the center and the date and time to the right.
 */
class PdfFooter
{
    /**
     * The top border.
     */
    private readonly PdfBorder $border;

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
        $this->border = PdfBorder::top();
    }

    /**
     * Output this content to the parent document.
     */
    public function output(): void
    {
        $parent = $this->parent;
        $parent->useCellMargin(function () use ($parent): void {
            // position and cells width
            $parent->SetY(-PdfDocument::FOOTER_OFFSET);
            $cellWidth = $parent->getPrintableWidth() / 3.0;
            // style
            PdfStyle::default()->setFontSize(8)->apply($parent);
            // pages (left) +  text and url (center) + date (right)
            $this->outputText($this->getPage(), $cellWidth, PdfTextAlignment::LEFT)
                ->outputText($this->content ?? '', $cellWidth, PdfTextAlignment::CENTER, $this->url ?? '')
                ->outputText($this->getDate(), $cellWidth, PdfTextAlignment::RIGHT);
            // reset
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
        $page = $parent->PageNo();
        if ($parent instanceof AbstractReport) {
            return $parent->trans('report.page', ['{0}' => $page, '{1}' => '{nb}']);
        }

        return "Page $page / {nb}";
    }

    /**
     * Output the given text.
     */
    private function outputText(string $text, float $cellWidth, PdfTextAlignment $align, string $link = ''): self
    {
        $this->parent->Cell(
            w: $cellWidth,
            txt: $text,
            border: $this->border,
            align: $align,
            link: $link
        );

        return $this;
    }
}
