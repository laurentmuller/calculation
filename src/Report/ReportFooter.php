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

namespace App\Report;

use App\Pdf\PdfFont;
use App\Pdf\PdfStyle;
use App\Utils\FormatUtils;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfBorder;

/**
 * Class to output footer in PDF documents.
 *
 * The page and total pages are output to the left, the content (if any) to the center and the date and time to the right.
 */
class ReportFooter
{
    /**
     * The footer offset in millimeters.
     */
    final public const FOOTER_OFFSET = 15.0;

    /**
     * The content text.
     */
    private ?string $content = null;

    /*
     * The formatted date.
     */
    private ?string $date = null;

    /**
     * The link.
     */
    private ?string $url = null;

    public function __construct(private readonly AbstractReport $parent)
    {
    }

    /**
     * Output this content to the parent document.
     */
    public function output(): void
    {
        $this->parent->useCellMargin(fn () => $this->outputTexts());
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
     * Gets the formatted pages.
     */
    private function getPages(): string
    {
        $parent = $this->parent;

        return $parent->trans('report.page', ['{0}' => $parent->getPage(), '{1}' => '{nb}']);
    }

    /**
     * Output the given text.
     */
    private function outputText(string $text, float $width, PdfTextAlignment $align, ?string $link = null): self
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

    private function outputTexts(): void
    {
        $parent = $this->parent;
        $parent->setY(-self::FOOTER_OFFSET);
        $width = $parent->getPrintableWidth() / 3.0;
        PdfStyle::default()->setFontSize(PdfFont::DEFAULT_SIZE - 1.0)->apply($parent);
        $this->outputText($this->getPages(), $width, PdfTextAlignment::LEFT)
            ->outputText($this->content ?? '', $width, PdfTextAlignment::CENTER, $this->url)
            ->outputText($this->getDate(), $width, PdfTextAlignment::RIGHT);
        $parent->resetStyle();
    }
}