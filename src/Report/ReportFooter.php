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

use App\Pdf\PdfStyle;
use App\Utils\DateUtils;
use App\Utils\FormatUtils;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfBorder;
use fpdf\PdfDocument;

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
    final public const FOOTER_OFFSET = 13.0;

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
        $this->parent->useCellMargin(fn () => $this->outputFooter());
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
     * Output the given text.
     */
    private function outputCell(?string $text, float $width, PdfTextAlignment $align, ?string $link = null): self
    {
        $text ??= '';
        $this->parent->cell(
            width: $width,
            height: PdfDocument::LINE_HEIGHT - 2.0,
            text: $text,
            border: PdfBorder::top(),
            align: $align,
            link: '' !== $text ? $link : null
        );

        return $this;
    }

    private function outputContent(float $width): self
    {
        return $this->outputCell($this->content, $width, PdfTextAlignment::CENTER, $this->url);
    }

    private function outputDate(float $width): void
    {
        $text = $this->date ??= FormatUtils::formatDateTime(DateUtils::createDatePoint());

        $this->outputCell($text, $width, PdfTextAlignment::RIGHT);
    }

    private function outputFooter(): void
    {
        $parent = $this->parent;
        $parent->setY(-self::FOOTER_OFFSET);
        $width = $parent->getPrintableWidth() / 3.0;
        PdfStyle::default()->setFontSize(6.0)->apply($parent);
        $this->outputPages($width)
            ->outputContent($width)
            ->outputDate($width);
        $parent->resetStyle();
    }

    private function outputPages(float $width): self
    {
        $parent = $this->parent;
        $text = $parent->trans('report.page', [
            '{0}' => $parent->getPage(),
            '{1}' => $parent->getAliasNumberPages(),
        ]);

        return $this->outputCell($text, $width, PdfTextAlignment::LEFT);
    }
}
