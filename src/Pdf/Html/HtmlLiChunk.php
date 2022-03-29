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

use App\Pdf\PdfFont;
use App\Report\HtmlReport;

/**
 * Specialized chunk for HTML list item (li).
 *
 * @author Laurent Muller
 */
class HtmlLiChunk extends HtmlParentChunk
{
    /**
     * Constructor.
     *
     * @param string               $name   the tag name
     * @param HtmlParentChunk|null $parent the parent chunk
     */
    public function __construct(protected string $name, ?HtmlParentChunk $parent = null)
    {
        parent::__construct($name, $parent);
    }

    /**
     * {@inheritdoc}
     */
    public function outputChildren(HtmlReport $report): void
    {
        $margin = $this->getBulletMargin($report);
        $this->applyMargins($report, $margin, 0, function (HtmlReport $report): void {
            parent::outputChildren($report);
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function getOutputText(): ?string
    {
        if (null !== ($parent = $this->getParentList())) {
            if ($parent instanceof HtmlUlChunk) {
                return \chr(149);
            } elseif ($parent instanceof HtmlOlChunk) {
                return $parent->getBulletChunk($this);
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function outputText(HtmlReport $report, string $text): void
    {
        $this->applyFont($report, $this->findFont(), function (HtmlReport $report) use ($text): void {
            $width = $this->getBulletMargin($report);
            $height = \max($report->getFontSize(), self::LINE_HEIGHT);
            $report->Cell($width, $height, $text, self::BORDER_NONE, self::MOVE_TO_RIGHT, self::ALIGN_RIGHT);
        });
    }

    /**
     * Finds the parent's font.
     */
    private function findFont(): ?PdfFont
    {
        $chunk = $this->findChild(self::TEXT);
        while ($chunk && !$chunk->hasStyle()) {
            $chunk = $chunk->getParent();
        }
        if (null !== $chunk) {
            $style = $chunk->getStyle();
            if (null !== $style) {
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
        if (null !== ($parent = $this->getParentList())) {
            if ($parent instanceof HtmlUlChunk) {
                $text = \chr(149);
            } elseif ($parent instanceof HtmlOlChunk) {
                $text = $parent->getBulletMaximum();
            }
        }

        if ($text) {
            $this->applyFont($report, $this->findFont(), function (HtmlReport $report) use (&$width, $text): void {
                $width = $report->GetStringWidth($text);
            });
        }

        return (float) $width;
    }

    /**
     * Finds the ordered or the unordered parent's list.
     */
    private function getParentList(): ?HtmlParentChunk
    {
        return $this->findParent(self::LIST_ORDERED, self::LIST_UNORDERED);
    }
}
