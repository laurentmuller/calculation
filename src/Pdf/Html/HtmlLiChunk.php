<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
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
     * @param string          $name   the tag name
     * @param HtmlParentChunk $parent the parent chunk
     */
    public function __construct(string $name, ?HtmlParentChunk $parent = null)
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
        if ($parent = $this->getParentList()) {
            switch ($parent->getName()) {
                case self::LIST_UNORDERED:
                    return \chr(149);

                case self::LIST_ORDERED:
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
     *
     * @return PdfFont|null the parent font, if found; <code>null</code> otherwise
     */
    private function findFont(): ?PdfFont
    {
        $chunk = $this->findChild(self::TEXT);
        while ($chunk && !$chunk->hasStyle()) {
            $chunk = $chunk->getParent();
        }
        if ($chunk) {
            return $chunk->getStyle()->getFont();
        }

        return null;
    }

    /**
     * Gets the bullet margin.
     *
     * @param HtmlReport $report the report used to mesure the margin
     *
     * @return float the margin
     */
    private function getBulletMargin(HtmlReport $report): float
    {
        $width = 0;
        $text = null;
        if ($parent = $this->getParentList()) {
            switch ($parent->getName()) {
                case self::LIST_UNORDERED:
                    $text = \chr(149);
                    break;

                case self::LIST_ORDERED:
                    $text = $parent->getBulletMaximum();
                    break;
            }
        }

        if ($text) {
            $this->applyFont($report, $this->findFont(), function (HtmlReport $report) use (&$width, $text): void {
                $width = $report->GetStringWidth($text);
            });
        }

        return $width;
    }

    /**
     * Finds the ordered or the unorders parent's list.
     *
     * @return HtmlParentChunk|null the parent, if found; <code>null</code> otherwise
     */
    private function getParentList(): ?HtmlParentChunk
    {
        return $this->findParent(self::LIST_ORDERED, self::LIST_UNORDERED);
    }
}
