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
class PdfFooter implements PdfConstantsInterface
{
    /**
     * the parent document.
     */
    protected PdfDocument $parent;
    /**
     * The footer text.
     */
    protected ?string $text = null;

    /**
     * The footer URL.
     */
    protected ?string $url = null;

    /**
     * Constructor.
     */
    public function __construct(PdfDocument $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Output this content to the parent document.
     */
    public function output(): void
    {
        // margins
        $margins = $this->parent->setCellMargin(0);

        // position and cells width
        $this->parent->SetY(PdfDocument::FOOTER_OFFSET);
        $cellWidth = $this->parent->getPrintableWidth() / 3;

        // style and line color
        PdfStyle::getDefaultStyle()->setFontSize(8)->apply($this->parent);

        // pages (left) +  text and url (center) + date (right)
        $this->ouputText($this->getPage(), $cellWidth, self::ALIGN_LEFT)
            ->ouputText($this->text ?? '', $cellWidth, self::ALIGN_CENTER, $this->url ?? '')
            ->ouputText($this->getDate(), $cellWidth, self::ALIGN_RIGHT);

        // reset
        $this->parent->setCellMargin($margins);
        $this->parent->resetStyle();
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
     * Gets the formatted current date.
     */
    private function getDate(): string
    {
        return FormatUtils::formatDateTime(new \DateTime(), \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT);
    }

    /**
     * Gets the formatted current and total pages.
     */
    private function getPage(): string
    {
        $page = $this->parent->PageNo();
        if ($this->parent instanceof AbstractReport) {
            return $this->parent->trans('report.page', ['{0}' => $page, '{1}' => '{nb}']);
        }

        return "Page $page / {nb}";
    }

    /**
     * Output the given text.
     */
    private function ouputText(string $text, float $cellWidth, string $align, string $link = ''): self
    {
        $this->parent->Cell($cellWidth, self::LINE_HEIGHT, $text, self::BORDER_TOP, self::MOVE_TO_RIGHT, $align, false, $link);

        return $this;
    }
}
