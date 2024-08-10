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

namespace App\Pdf\Html;

use App\Report\HtmlReport;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfDocument;

/**
 * Represents a text chunk.
 */
class HtmlTextChunk extends AbstractHtmlChunk
{
    /**
     * The names of parents to use with multi-cell.
     */
    private const PARENT_MULTI_CELL = [
        HtmlTag::H1,
        HtmlTag::H2,
        HtmlTag::H3,
        HtmlTag::H4,
        HtmlTag::H5,
        HtmlTag::H6,
        HtmlTag::PARAGRAPH,
        HtmlTag::SPAN,
        HtmlTag::LIST_ITEM,
    ];

    /**
     * The text.
     */
    private ?string $text = null;

    public function isNewLine(): bool
    {
        // check if the next chunk is a parent chunk
        $parent = $this->getParent();
        if ($parent instanceof HtmlParentChunk) {
            $index = $this->index();
            $count = $parent->count();
            if (-1 !== $index && $index < $count - 1) {
                $next = $parent->getChildren()[$index + 1];

                return $next->is(HtmlTag::LIST_ORDERED, HtmlTag::LIST_UNORDERED);
            }
        }

        // default
        return parent::isNewLine();
    }

    /**
     * Sets the text.
     */
    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    protected function getOutputText(): ?string
    {
        return $this->text;
    }

    protected function outputText(HtmlReport $report, string $text): void
    {
        $parent = $this->getParent();
        if ($parent instanceof HtmlParentChunk) {
            // bookmark
            if ($parent->isBookmark()) {
                $report->addBookmark($text, true, $parent->getBookmarkLevel());
            }
            // special case when parent contains only this text
            if (1 === $parent->count() && $parent->is(...self::PARENT_MULTI_CELL)) {
                $align = $parent->getAlignment();
                switch ($align) {
                    case PdfTextAlignment::RIGHT:
                    case PdfTextAlignment::CENTER:
                    case PdfTextAlignment::JUSTIFIED:
                        $height = \max($report->getFontSize(), PdfDocument::LINE_HEIGHT);
                        $report->multiCell(height: $height, text: $text, align: $align);
                        $report->moveY(-$report->getLastHeight());

                        return;
                    default:
                        break;
                }
            }
        }
        parent::outputText($report, $text);
    }
}
