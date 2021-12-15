<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Pdf;

use App\Report\AbstractReport;
use App\Util\FormatUtils;

/**
 * Class to output footer in PDF documents.
 *
 * @author Laurent Muller
 */
class PdfFooter implements PdfDocumentUpdaterInterface, PdfConstantsInterface
{
    /**
     * The footer text.
     */
    protected ?string $text = null;

    /**
     * The footer URL.
     */
    protected ?string $url = null;

    /**
     * {@inheritDoc}
     */
    public function apply(PdfDocument $doc): void
    {
        // font
        $style = PdfStyle::getDefaultStyle()->setFontSize(8);
        $style->apply($doc);

        // margins
        $margins = $doc->setCellMargin(0);

        // position and cells width
        $doc->SetY(PdfDocument::FOOTER_OFFSET);
        $cellWidth = $doc->getPrintableWidth() / 3;

        // pages
        $doc->Cell($cellWidth, self::LINE_HEIGHT, $this->getPage($doc), self::BORDER_TOP, self::MOVE_TO_RIGHT, self::ALIGN_LEFT);

        // text and url (if any)
        $doc->Cell($cellWidth, self::LINE_HEIGHT, $this->text ?: '', self::BORDER_TOP, self::MOVE_TO_RIGHT, self::ALIGN_CENTER, false, $this->url);

        // date
        $doc->Cell($cellWidth, self::LINE_HEIGHT, $this->getDate(), self::BORDER_TOP, self::MOVE_TO_RIGHT, self::ALIGN_RIGHT);

        // reset
        $doc->setCellMargin($margins);
        $doc->resetStyle();
    }

    /**
     * Sets the content.
     */
    public function setContent(string $text, ?string $url): self
    {
        $this->text = $text;
        $this->url = $url;

        return $this;
    }

    /**
     * Gets the current formatted date.
     */
    private function getDate(): string
    {
        return FormatUtils::formatDateTime(new \DateTime(), \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT);
    }

    /**
     * Gets the formatted current page and total pages.
     */
    private function getPage(PdfDocument $doc): string
    {
        $page = $doc->PageNo();
        if ($doc instanceof AbstractReport) {
            return $doc->trans('report.page', ['{0}' => $page, '{1}' => '{nb}']);
        }

        return "Page $page / {nb}";
    }
}
