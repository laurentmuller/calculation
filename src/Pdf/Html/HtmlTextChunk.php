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

use App\Report\HtmlReport;
use App\Utils\Utils;

/**
 * Represents a text chunk.
 *
 * @author Laurent Muller
 */
class HtmlTextChunk extends HtmlChunk
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
     *
     * @var string
     */
    protected $text;

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        if ($this->isEmpty()) {
            return parent::__toString();
        }

        $shortName = Utils::getShortName($this);

        return \sprintf('%s("%s")', $shortName, $this->text);
    }

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
        if ($parent = $this->parent) {
            $index = $this->index();
            $count = $parent->count();
            if (-1 !== $index && $index < $count - 1) {
                /** @var HtmlChunk $next */
                $next = $parent->getChildren()[$index + 1];

                return $next->is(self::LIST_ORDERED, self::LIST_UNORDERED);
            }
        }

        // default
        return  parent::isNewLine();
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
        /** @var HtmlParentChunk $parent */
        $parent = $this->parent;

        // special case when parent contains only this text
        if ($parent && 1 === $parent->count() && $parent->is(...self::PARENT_MULTI_CELL)) {
            $align = $parent->getAlignment();
            switch ($align) {
                case self::ALIGN_RIGHT:
                case self::ALIGN_CENTER:
                case self::ALIGN_JUSTIFIED:
                    $height = \max($report->getFontSize(), self::LINE_HEIGHT);
                    $report->MultiCell(0, $height, $text, 0, $align); //, true);
                    $report->SetY($report->GetY() - $report->getLastHeight());

                    return;
            }
        }

        parent::outputText($report, $text);
    }
}
