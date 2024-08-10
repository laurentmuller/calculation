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
use App\Traits\ArrayTrait;
use App\Utils\StringUtils;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfBorder;
use fpdf\PdfDocument;

/**
 * Represents an HTML chunk.
 */
abstract class AbstractHtmlChunk
{
    use ArrayTrait;

    /**
     * The bookmark.
     */
    private bool $bookmark = false;

    /**
     * The bookmark level.
     *
     * @psalm-var non-negative-int
     */
    private int $bookmarkLevel = 0;

    /**
     * The class names.
     *
     * @var string[]
     */
    private array $classes = [];

    /**
     * The parent chunk.
     */
    private ?HtmlParentChunk $parent = null;

    /**
     * The style.
     */
    private ?HtmlStyle $style = null;

    /**
     * @param string           $name      the tag name
     * @param ?HtmlParentChunk $parent    the parent chunk
     * @param ?string          $className the class name
     */
    public function __construct(private readonly string $name, ?HtmlParentChunk $parent = null, ?string $className = null)
    {
        $parent?->add($this);
        $this->setClassName($className);
    }

    /**
     * Apply this style (if any) to the given report.
     *
     * @param HtmlReport $report the report to update
     */
    public function applyStyle(HtmlReport $report): static
    {
        $this->style?->apply($report);

        return $this;
    }

    /**
     * Gets the text alignment from this style or left, if none.
     */
    public function getAlignment(): PdfTextAlignment
    {
        return $this->style?->getAlignment() ?? PdfTextAlignment::LEFT;
    }

    /**
     * Return the bookmark level.
     *
     * @psalm-return non-negative-int
     */
    public function getBookmarkLevel(): int
    {
        return $this->bookmarkLevel;
    }

    /**
     * Gets the bottom margin from this style or 0 if none.
     */
    public function getBottomMargin(): float
    {
        return $this->style?->getBottomMargin() ?? 0;
    }

    /**
     * Gets the left margin from this style or 0 if none.
     */
    public function getLeftMargin(): float
    {
        return $this->style?->getLeftMargin() ?? 0;
    }

    /**
     * Gets the tag name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Gets the parent.
     */
    public function getParent(): ?HtmlParentChunk
    {
        return $this->parent;
    }

    /**
     * Gets the right margin from this style or 0 if none.
     */
    public function getRightMargin(): float
    {
        return $this->style?->getRightMargin() ?? 0;
    }

    /**
     * Gets the style.
     */
    public function getStyle(): ?HtmlStyle
    {
        return $this->style;
    }

    /**
     * Gets the top margin from this style or 0 if none.
     */
    public function getTopMargin(): float
    {
        return $this->style?->getTopMargin() ?? 0;
    }

    /**
     * Returns if a style is defined.
     */
    public function hasStyle(): bool
    {
        return $this->style instanceof HtmlStyle;
    }

    /**
     * Gets index of this chunk.
     *
     * @return int the index; or -1 if is root
     */
    public function index(): int
    {
        return $this->parent?->indexOf($this) ?? -1;
    }

    /**
     * Returns if this name matches one of the given tags, ignoring case consideration.
     */
    public function is(HtmlTag ...$tags): bool
    {
        foreach ($tags as $tag) {
            if ($tag->match($this->name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return if the bookmark is set.
     */
    public function isBookmark(): bool
    {
        return $this->bookmark;
    }

    /**
     * Returns if a new line must add at the end of the report output.
     */
    public function isNewLine(): bool
    {
        return false;
    }

    /**
     * Output this chunk to the given report.
     */
    public function output(HtmlReport $report): void
    {
        $this->applyStyle($report);
        $text = $this->getOutputText();
        if (StringUtils::isString($text)) {
            $this->outputText($report, $text);
        }
    }

    /**
     * Sets the class name.
     */
    public function setClassName(?string $className): static
    {
        $this->classes = [];
        if (StringUtils::isString($className)) {
            $this->classes = $this->getUniqueFiltered(\explode(' ', \strtolower($className)));
        }

        return $this->updateStyle();
    }

    /**
     * Sets the style.
     */
    public function setStyle(?HtmlStyle $style): static
    {
        $this->style = $style;

        return $this;
    }

    /**
     * Apply the given font (if any), call the callback and restore the previous font.
     *
     * @param HtmlReport $report   the report to set and restore font
     * @param ?PdfFont   $font     the font to apply
     * @param callable   $callback the callback to call after the font has been set. The report is passed as argument.
     *
     * @psalm-param callable(HtmlReport):void $callback
     */
    protected function applyFont(HtmlReport $report, ?PdfFont $font, callable $callback): void
    {
        if ($font instanceof PdfFont) {
            $oldFont = $report->applyFont($font);
            $callback($report);
            $report->applyFont($oldFont);
        } else {
            $callback($report);
        }
    }

    /**
     * Apply the given margins (if different from 0), call the callback and restore the previous margins.
     *
     * @param HtmlReport $report      the report to set and restore margins
     * @param float      $leftMargin  the left margin to add
     * @param float      $rightMargin the right margin to add
     * @param callable   $callback    the callback to call after the margins have been set. The report is passed
     *                                as an argument.
     *
     * @psalm-param callable(HtmlReport):void $callback
     */
    protected function applyMargins(HtmlReport $report, float $leftMargin, float $rightMargin, callable $callback): void
    {
        // get margins
        $oldLeft = $report->getLeftMargin();
        $oldRight = $report->getRightMargin();
        $newLeft = $oldLeft + $leftMargin;
        $newRight = $oldRight + $rightMargin;

        // apply
        if ($newLeft !== $oldLeft) {
            $report->updateLeftMargin($newLeft);
        }
        if ($newRight !== $oldRight) {
            $report->updateRightMargin($newRight);
        }

        // call function
        $callback($report);

        // restore
        if ($newLeft !== $oldLeft) {
            $report->updateLeftMargin($oldLeft);
        }
        if ($newRight !== $oldRight) {
            $report->updateRightMargin($oldRight);
        }
    }

    /**
     * Gets the report output text.
     */
    protected function getOutputText(): ?string
    {
        return null;
    }

    /**
     * Output the given text to the report.
     */
    protected function outputText(HtmlReport $report, string $text): void
    {
        $border = $this->getParentBorder();
        $height = \max($report->getFontSize(), PdfDocument::LINE_HEIGHT);
        if ($border instanceof PdfBorder && !$border->isNone()) {
            $required = $report->getStringWidth($text) + 2.0 * $report->getCellMargin();
            if ($required > $report->getRemainingWidth()) {
                $report->multiCell(height: $height, text: $text, border: $border);
            } else {
                $report->cell($required, $height, $text, $border);
            }
        } else {
            $report->write($text, $height);
        }
    }

    /**
     * Sets the parent.
     */
    protected function setParent(?HtmlParentChunk $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    private function getParentBorder(): ?PdfBorder
    {
        return $this->parent?->style?->getBorder();
    }

    private function parseBookmark(string $class): void
    {
        // bookmark
        if ('bookmark' === $class) {
            $this->bookmark = true;
        }

        // level
        $regex = '/bookmark-(\d+)/';
        if (1 === \preg_match($regex, $class, $matches)) {
            /** @psalm-var non-negative-int $level */
            $level = (int) $matches[1];
            $this->bookmarkLevel = $level;
            $this->bookmark = true;
        }
    }

    /**
     * Update this style, depending on the tag name and class.
     */
    private function updateStyle(): static
    {
        // bookmark
        foreach ($this->classes as $class) {
            $this->parseBookmark($class);
        }

        // create style
        $style = HtmlTag::getStyle($this->name);
        if ($style instanceof HtmlStyle) {
            foreach ($this->classes as $class) {
                $style->update($class);
            }
        }

        return $this->setStyle($style);
    }
}
