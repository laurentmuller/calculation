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

use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\PdfDocument;
use App\Pdf\PdfFont;
use App\Report\HtmlReport;

/**
 * Specialized chunk for HTML list item (li).
 */
class HtmlLiChunk extends HtmlParentChunk
{
    public function outputChildren(HtmlReport $report): void
    {
        $margin = $this->getBulletMargin($report);
        $this->applyMargins($report, $margin, 0, function (HtmlReport $report): void {
            parent::outputChildren($report);
        });
    }

    protected function getOutputText(): ?string
    {
        $parent = $this->getParent();
        if ($parent instanceof AbstractHtmlListChunk) {
            return $parent->getBulletText($this);
        }

        return null;
    }

    protected function outputText(HtmlReport $report, string $text): void
    {
        $this->applyFont($report, $this->findFont(), function (HtmlReport $report) use ($text): void {
            $width = $this->getBulletMargin($report);
            $height = \max($report->getFontSize(), PdfDocument::LINE_HEIGHT);
            $report->Cell(
                w: $width,
                h: $height,
                txt: $text,
                align: PdfTextAlignment::RIGHT
            );
        });
    }

    /**
     * Finds the parent's font.
     */
    private function findFont(): ?PdfFont
    {
        $chunk = $this->findChild(HtmlTag::TEXT);
        while ($chunk && !$chunk->hasStyle()) {
            $chunk = $chunk->getParent();
        }
        if ($chunk instanceof AbstractHtmlChunk) {
            $style = $chunk->getStyle();
            if ($style instanceof HtmlStyle) {
                return $style->getFont();
            }
        }

        return null;
    }

    /**
     * Gets the bullet margin.
     */
    private function getBulletMargin(HtmlReport $report): float
    {
        $width = 0;
        $text = null;
        $parent = $this->getParent();
        if ($parent instanceof AbstractHtmlListChunk) {
            $text = $parent->getBulletLast();
        }

        if ($text) {
            $this->applyFont($report, $this->findFont(), function (HtmlReport $report) use (&$width, $text): void {
                $width = $report->GetStringWidth($text);
            });
        }

        return (float) $width;
    }
}
