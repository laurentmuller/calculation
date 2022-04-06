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

namespace App\Pdf\Html;

use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\PdfDocument;
use App\Report\HtmlReport;
use App\Util\Utils;

/**
 * Represents a text chunk.
 *
 * @author Laurent Muller
 */
class HtmlTextChunk extends AbstractHtmlChunk
{
    /**
     * The names of parents to use with multi-cell.
     */
    private const PARENT_MULTI_CELL = [
        self::H1,
        self::H2,
        self::H3,
        self::H4,
        self::H5,
        self::H6,
        self::PARAGRAPH,
        self::LIST_ITEM,
    ];

    /**
     * The text.
     */
    protected ?string $text = null;

    /**
     * Gets the text.
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * Returns if this text is <code>null</code> or empty.
     */
    public function isEmpty(): bool
    {
        return !Utils::isString($this->text);
    }

    /**
     * {@inheritdoc}
     */
    public function isNewLine(): bool
    {
        // check if the next chunk is a parent chunk
        if (null !== ($parent = $this->parent)) {
            $index = $this->index();
            $count = $parent->count();
            if (-1 !== $index && $index < $count - 1) {
                $next = $parent->getChildren()[$index + 1];

                return $next->is(self::LIST_ORDERED, self::LIST_UNORDERED);
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

    /**
     * {@inheritdoc}
     */
    protected function getOutputText(): ?string
    {
        return $this->getText();
    }

    /**
     * {@inheritdoc}
     */
    protected function outputText(HtmlReport $report, string $text): void
    {
        $parent = $this->getParent();

        // special case when parent contains only this text
        if (null !== $parent && 1 === $parent->count() && $parent->is(...self::PARENT_MULTI_CELL)) {
            $align = $parent->getAlignment();
            switch ($align) {
                case PdfTextAlignment::RIGHT:
                case PdfTextAlignment::CENTER:
                case PdfTextAlignment::JUSTIFIED:
                    $height = \max($report->getFontSize(), PdfDocument::LINE_HEIGHT);
                    $report->MultiCell(0, $height, $text, 0, $align);
                    $report->SetY($report->GetY() - $report->getLastHeight());

                    return;
            }
        }

        parent::outputText($report, $text);
    }
}
