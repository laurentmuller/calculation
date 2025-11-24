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

use App\Pdf\PdfFont;
use App\Report\HtmlReport;
use App\Utils\StringUtils;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfDocument;

/**
 * A specialized chunk for HTML list item (li).
 */
class HtmlLiChunk extends HtmlParentChunk
{
    public function __construct(?HtmlParentChunk $parent = null, ?string $className = null)
    {
        parent::__construct(HtmlTag::LIST_ITEM, $parent, $className);
    }

    #[\Override]
    public function outputChildren(HtmlReport $report): void
    {
        $margin = $this->getBulletMargin($report);
        $this->applyMargins($report, $margin, 0, function (HtmlReport $report): void {
            parent::outputChildren($report);
        });
    }

    #[\Override]
    protected function getOutputText(): ?string
    {
        $parent = $this->getParent();
        if ($parent instanceof AbstractHtmlListChunk) {
            return $parent->getBulletText($this);
        }

        return null;
    }

    #[\Override]
    protected function outputText(HtmlReport $report, string $text): void
    {
        $this->applyFont($report, $this->findFont(), function (HtmlReport $report) use ($text): void {
            $width = $this->getBulletMargin($report);
            $height = \max($report->getFontSize(), PdfDocument::LINE_HEIGHT);
            $report->cell(
                width: $width,
                height: $height,
                text: $text,
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
        while ($chunk instanceof AbstractHtmlChunk && !$chunk->hasStyle()) {
            $chunk = $chunk->getParent();
        }

        return $chunk?->getStyle()?->getFont();
    }

    /**
     * Gets the bullet margin.
     */
    private function getBulletMargin(HtmlReport $report): float
    {
        $width = 0.0;
        $text = null;
        $parent = $this->getParent();
        if ($parent instanceof AbstractHtmlListChunk) {
            $text = $parent->getLastBulletText();
        }

        if (StringUtils::isString($text)) {
            $this->applyFont($report, $this->findFont(), static function (HtmlReport $report) use (&$width, $text): void {
                $width = $report->getStringWidth($text);
            });
        }

        return $width;
    }
}
