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

use App\Report\HtmlReport;
use App\Traits\MathTrait;

/**
 * Represents a chunk container.
 *
 * @author Laurent Muller
 */
class HtmlParentChunk extends AbstractHtmlChunk implements \Countable
{
    use MathTrait;

    /**
     * The children chunks.
     *
     * @var AbstractHtmlChunk[]
     */
    protected $children;

    /**
     * Constructor.
     *
     * @param string          $name   the tag name
     * @param HtmlParentChunk $parent the parent chunk
     */
    public function __construct(string $name, ?self $parent = null)
    {
        parent::__construct($name, $parent);
        $this->children = [];
    }

    /**
     * Adds a child to the collection of children. Do nothing if the child is already in this collection.
     *
     * @param AbstractHtmlChunk $child the child to add
     */
    public function add(AbstractHtmlChunk $child): self
    {
        if (!\in_array($child, $this->children, true)) {
            $child->setParent($this);
            $this->children[] = $child;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return int the number of children
     */
    public function count(): int
    {
        return \count($this->children);
    }

    /**
     * Finds the first child for the given the tag names.
     *
     * @param string[] ...$names the tag names to search for
     *
     * @return AbstractHtmlChunk|null the child, if found; <code>null</code> otherwise
     */
    public function findChild(string ...$names): ?AbstractHtmlChunk
    {
        foreach ($this->children as $child) {
            if ($child->is(...$names)) {
                return $child;
            }
            if ($child instanceof self) {
                $chunk = $child->findChild(...$names);
                if (null !== $chunk) {
                    return $chunk;
                }
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
     * @param AbstractHtmlChunk $chunk the child chunk
     *
     * @return int the index, if found; -1 otherwise
     */
    public function indexOf(AbstractHtmlChunk $chunk): int
    {
        $index = \array_search($chunk, $this->children, true);

        return false === $index ? -1 : (int) $index;
    }

    /**
     * Checks whether the children is empty (contains no elements).
     *
     * @return bool true if the collection is empty, false otherwise
     */
    public function isEmpty(): bool
    {
        return empty($this->children);
    }

    /**
     * {@inheritdoc}
     */
    public function isNewLine(): bool
    {
        switch ($this->name) {
            case self::H1:
            case self::H2:
            case self::H3:
            case self::H4:
            case self::H5:
            case self::H6:
            case self::PARAGRAPH:
               return true;
            case self::LIST_ITEM:
                return !self::isLastNewLine($this);
            default:
                return parent::isNewLine();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function output(HtmlReport $report): void
    {
        // update margins
        $this->applyMargins($report, $this->getLeftMargin(), $this->getRightMargin(), function (HtmlReport $report): void {
            // top margin
            $this->moveY($report, $this->getTopMargin());

            // default
            parent::output($report);

            // children
            $this->outputChildren($report);

            // bottom margin
            $this->moveY($report, $this->getBottomMargin());

            // restore style
            if (null !== $this->getParent()) {
                $this->getParent()->applyStyle($report);
            }
        });
    }

    /**
     * Output this children chunks (if any) to the given report.
     *
     * @param HtmlReport $report the report to write to
     */
    public function outputChildren(HtmlReport $report): void
    {
        foreach ($this->children as $child) {
            // output
            $child->output($report);

            // new line
            if ($child->isNewLine()) {
                $report->Ln();
            }
        }
    }

    /**
     * Remove a child from the collection of children. Do nothing if the child is not in this collection.
     *
     * @param AbstractHtmlChunk $child the child to remove
     */
    public function remove(AbstractHtmlChunk $child): self
    {
        if (\in_array($child, $this->children, true)) {
            $child->setParent(null);
            $this->children = \array_diff($this->children, [$child]);
        }

        return $this;
    }

    /**
     * Returns if the last child has a new line.
     *
     * @param HtmlParentChunk $parent the parent to get the last child
     *
     * @return bool true if new line
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

        return $child instanceof self && $child->isLastNewLine($child);
    }

    /**
     * Move up/down the current y position of the report.
     * Do nothing if the delta value is equal to 0.
     *
     * @param HtmlReport $report the report to update
     * @param float      $delta  the move up/down value
     */
    private function moveY(HtmlReport $report, float $delta): void
    {
        if (!$this->isFloatZero($delta)) {
            $report->SetY($report->GetY() + $delta, false);
        }
    }
}
