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
use fpdf\PdfDocument;

/**
 * Represents a chunk container.
 */
class HtmlParentChunk extends AbstractHtmlChunk implements \Countable
{
    /**
     * The tags to keep with the next chunk.
     */
    private const KEEP_WITH_NEXT = [
        HtmlTag::H1,
        HtmlTag::H2,
        HtmlTag::H3,
        HtmlTag::H4,
        HtmlTag::H5,
        HtmlTag::H6,
        HtmlTag::LIST_ORDERED,
        HtmlTag::LIST_UNORDERED,
    ];

    /**
     * @var AbstractHtmlChunk[]
     */
    private array $children = [];

    /**
     * Adds a child to this collection of children. Do nothing if the child is already in this collection.
     */
    public function add(AbstractHtmlChunk $child): static
    {
        if (!\in_array($child, $this->children, true)) {
            $child->setParent($this);
            $this->children[] = $child;
        }

        return $this;
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->children);
    }

    /**
     * Finds the first child for the given tags.
     */
    public function findChild(HtmlTag ...$tags): ?AbstractHtmlChunk
    {
        if ($this->isEmpty()) {
            return null;
        }

        foreach ($this->children as $child) {
            if ($child->is(...$tags)) {
                return $child;
            }
            if (!$child instanceof self) {
                continue;
            }
            $chunk = $child->findChild(...$tags);
            if ($chunk  instanceof AbstractHtmlChunk) {
                return $chunk;
            }
        }

        return null;
    }

    /**
     * Gets the children.
     *
     * @return AbstractHtmlChunk[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Gets the index of the given child.
     *
     * @return int the index, if found; -1 otherwise
     */
    public function indexOf(AbstractHtmlChunk $chunk): int
    {
        $index = \array_search($chunk, $this->children, true);

        return false === $index ? -1 : (int) $index;
    }

    /**
     * Checks whether the children are empty (contains no elements).
     */
    public function isEmpty(): bool
    {
        return [] === $this->children;
    }

    #[\Override]
    public function isNewLine(): bool
    {
        $name = \strtolower($this->getName());

        return match (HtmlTag::tryFrom($name)) {
            HtmlTag::H1,
            HtmlTag::H2,
            HtmlTag::H3,
            HtmlTag::H4,
            HtmlTag::H5,
            HtmlTag::H6,
            HtmlTag::PARAGRAPH => true,
            HtmlTag::LIST_ITEM => !self::isLastNewLine($this),
            default => parent::isNewLine(),
        };
    }

    #[\Override]
    public function output(HtmlReport $report): void
    {
        $this->applyMargins(
            $report,
            $this->getLeftMargin(),
            $this->getRightMargin(),
            fn (HtmlReport $report) => $this->doOutput($report)
        );
    }

    /**
     * Output these children chunks (if any) to the given report.
     */
    public function outputChildren(HtmlReport $report): void
    {
        foreach ($this->children as $child) {
            $child->output($report);
            if ($child->isNewLine()) {
                $report->lineBreak();
            }
        }
    }

    /**
     * Remove a child from this collection of children. Do nothing if the child is not in this collection.
     */
    public function remove(AbstractHtmlChunk $child): static
    {
        $index = $this->indexOf($child);
        if (-1 === $index) {
            return $this;
        }

        $child->setParent(null);
        unset($this->children[$index]);

        return $this;
    }

    /**
     * Returns if the last child, if any, has a new line.
     */
    protected static function isLastNewLine(self $parent): bool
    {
        $child = \end($parent->children);
        if (false === $child) {
            return false;
        }
        if ($child->isNewLine()) {
            return true;
        }

        return $child instanceof self && $child::isLastNewLine($child);
    }

    private function doOutput(HtmlReport $report): void
    {
        if ($this->isPrintable($report)) {
            $report->moveY($this->getTopMargin(), false);
        } else {
            $report->addPage();
        }
        parent::output($report);
        $this->outputChildren($report);
        $report->moveY($this->getBottomMargin(), false);
        $this->getParent()?->applyStyle($report);
    }

    private function isPrintable(HtmlReport $report): bool
    {
        if ($this->isEmpty() || !$this->is(...self::KEEP_WITH_NEXT)) {
            return true;
        }

        return $report->isPrintable(
            PdfDocument::LINE_HEIGHT * 2.0 + $this->getTopMargin() + $this->getBottomMargin()
        );
    }
}
