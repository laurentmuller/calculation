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

use App\Pdf\Enums\PdfFontName;
use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\PdfBorder;
use App\Pdf\PdfDocument;
use App\Pdf\PdfFillColor;
use App\Pdf\PdfFont;
use App\Pdf\PdfTextColor;
use App\Report\HtmlReport;
use App\Util\StringUtils;

/**
 * Represents an HTML chunk.
 */
abstract class AbstractHtmlChunk implements HtmlConstantsInterface
{
    /**
     * The bookmark.
     */
    private bool $bookmark = false;

    /**
     * The bookmark level.
     */
    private int $bookmarkLevel = 0;

    /**
     * The class name.
     */
    private ?string $className = null;

    /**
     * The css style.
     */
    private ?string $css = null;

    /**
     * The parent chunk.
     */
    private ?HtmlParentChunk $parent = null;

    /**
     * The style.
     */
    private ?HtmlStyle $style = null;

    /**
     * Constructor.
     *
     * @param string           $name   the tag name
     * @param ?HtmlParentChunk $parent the parent chunk
     */
    public function __construct(private readonly string $name, ?HtmlParentChunk $parent = null)
    {
        $parent?->add($this);
        $this->updateStyle();
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
     * Finds the parent for the given the tag names.
     */
    public function findParent(string ...$names): ?HtmlParentChunk
    {
        $parent = $this->parent;
        while (null !== $parent && !$parent->is(...$names)) {
            $parent = $parent->getParent();
        }

        return $parent;
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
     * Gets the class name.
     */
    public function getClassName(): ?string
    {
        return $this->className;
    }

    /**
     * Gets the CSS style.
     */
    public function getCss(): ?string
    {
        return $this->css;
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
        return null !== $this->style;
    }

    /**
     * Gets index of this chunk.
     *
     * @return int the index; -1 if root
     */
    public function index(): int
    {
        return $this->parent?->indexOf($this) ?? -1;
    }

    /**
     * Returns if this tag name match the given one of the list of names.
     *
     * @return bool true if match
     */
    public function is(string ...$names): bool
    {
        foreach ($names as $name) {
            if (StringUtils::equalIgnoreCase($this->name, $name)) {
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
        if (\is_string($text) && '' !== $text) {
            $this->outputText($report, $text);
        }
    }

    /**
     * Sets the class name.
     */
    public function setClassName(?string $className): static
    {
        $this->className = null;
        if (null !== $className && '' !== $className) {
            $names = \array_unique(\array_filter(\explode(' ', \strtolower($className))));
            if ([] !== $names) {
                $this->className = \implode(' ', $names);
            }
        }

        return $this->updateStyle();
    }

    /**
     * Sets the CSS style.
     */
    public function setCss(?string $css): static
    {
        $this->css = $css;

        return $this;
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
     * Example:
     * <pre>
     * <code>
     *      $this->applyFont($report, $myFont, function(HtmlReport $report) {
     *          ...
     *      });
     * </code>
     * </pre>.
     *
     * @param HtmlReport $report   the report to set and restore font
     * @param ?PdfFont   $font     the font to apply
     * @param callable   $callback the callback to call after the font has been set. The report is passed as argument.
     *
     * @psalm-param callable(HtmlReport):void $callback
     */
    protected function applyFont(HtmlReport $report, ?PdfFont $font, callable $callback): void
    {
        if (null !== $font) {
            $oldFont = $report->applyFont($font);
            $callback($report);
            $report->applyFont($oldFont);
        } else {
            $callback($report);
        }
    }

    /**
     * Apply the given margins (if different from 0), call the callback and restore the previous margins.
     * Example:
     * <pre>
     * <code>
     *      $this->applyMargins($report, 10, 25, function(HtmlReport $report) {
     *          ...
     *      });
     * </code>
     * </pre>.
     *
     * @param HtmlReport $report      the report to set and restore margins
     * @param float      $leftMargin  the left margin to add
     * @param float      $rightMargin the right margin to add
     * @param callable   $callback    the callback to call after the margins has been set. The report is passed as argument.
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
     * By default, call the <code>write</code> method of the report.
     */
    protected function outputText(HtmlReport $report, string $text): void
    {
        $height = \max($report->getFontSize(), PdfDocument::LINE_HEIGHT);
        $report->Write($height, $text);
    }

    /**
     * Sets the parent.
     */
    protected function setParent(?HtmlParentChunk $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Update this style, depending on the CSS.
     */
    protected function updateCss(): static
    {
        if ($this->css) {
            $matches = [];
            if (\preg_match_all("/([\w-]+)\s*:\s*([^;]+)\s*;?/", $this->css, $matches, \PREG_SET_ORDER)) {
                $update = false;
                $style = $this->getStyle() ?? new HtmlStyle();
                foreach ($matches as $match) {
                    $name = \strtolower($match[1]);
                    $value = \trim($match[2]);
                    switch ($name) {
                        case 'color':
                            $color = PdfTextColor::create($value);
                            if (null !== $color) {
                                $style->setTextColor($color);
                                $update = true;
                            }
                            break;
                        case 'background-color':
                            $color = PdfFillColor::create($value);
                            if (null !== $color) {
                                $style->setFillColor($color);
                                $update = true;
                            }
                            break;
                    }
                }

                if ($update) {
                    $this->setStyle($style);
                }
            }
        }

        return $this;
    }

    private function parseAlignment(HtmlStyle $style, string $class): void
    {
        $alignment = match ($class) {
            'text-left' => PdfTextAlignment::LEFT,
            'text-right' => PdfTextAlignment::RIGHT,
            'text-center' => PdfTextAlignment::CENTER,
            'text-justify' => PdfTextAlignment::JUSTIFIED,
            default => null,
        };
        if (null !== $alignment) {
            $style->setAlignment($alignment);
        }
    }

    private function parseBookmark(string $class): self
    {
        // bookmark
        if ('bookmark' === $class) {
            $this->bookmark = true;
        }

        // level
        $matches = [];
        $regex = '/bookmark-(\d+)/';
        if (\preg_match($regex, $class, $matches)) {
            $this->bookmarkLevel = (int) $matches[1];
        }

        return $this;
    }

    /**
     * Parses the border class.
     *
     * @param HtmlStyle $style the style to update
     * @param string    $class the border class name
     */
    private function parseBorders(HtmlStyle $style, string $class): self
    {
        $border = match ($class) {
            'border' => PdfBorder::ALL,
            'border-top' => PdfBorder::TOP,
            'border-bottom' => PdfBorder::BOTTOM,
            'border-left' => PdfBorder::LEFT,
            'border-right' => PdfBorder::RIGHT,
            'border-0' => PdfBorder::NONE,
            'border-top-0' => PdfBorder::LEFT . PdfBorder::RIGHT . PdfBorder::BOTTOM,
            'border-left-0' => PdfBorder::RIGHT . PdfBorder::TOP . PdfBorder::BOTTOM,
            'border-right-0' => PdfBorder::LEFT . PdfBorder::TOP . PdfBorder::BOTTOM,
            'border-bottom-0' => PdfBorder::LEFT . PdfBorder::RIGHT . PdfBorder::TOP,
            default => null
        };
        if (null !== $border) {
            $style->setBorder($border);
        }

        return $this;
    }

    private function parseColor(HtmlStyle $style, string $class): self
    {
        $color = match ($class) {
            'text-primary' => HtmlBootstrapColors::PRIMARY->getTextColor(),
            'text-secondary' => HtmlBootstrapColors::SECONDARY->getTextColor(),
            'text-success' => HtmlBootstrapColors::SUCCESS->getTextColor(),
            'text-danger' => HtmlBootstrapColors::DANGER->getTextColor(),
            'text-warning' => HtmlBootstrapColors::WARNING->getTextColor(),
            'text-info' => HtmlBootstrapColors::INFO->getTextColor(),
            default => null,
        };

        if (null !== $color) {
            $style->setTextColor($color);
        }

        return $this;
    }

    private function parseFont(HtmlStyle $style, string $class): self
    {
        switch ($class) {
            case 'font-weight-bold':
                $style->bold(true);
                break;
            case 'font-italic':
                $style->setFontItalic(true);
                break;
            case 'font-weight-normal':
                $style->setFontRegular();
                break;
            case 'text-monospace':
                $style->setFontRegular()->setFontName(PdfFontName::COURIER);
                break;
        }

        return $this;
    }

    /**
     * Parses the margins class.
     *
     * @param HtmlStyle $style the style to update
     * @param string    $class the margins class name
     */
    private function parseMargins(HtmlStyle $style, string $class): self
    {
        $matches = [];
        $pattern = '/[m|p]([tblrxy])?.*-([012345])/im';
        if (\preg_match_all($pattern, $class, $matches, \PREG_SET_ORDER)) {
            $match = $matches[0];
            $value = (float) $match[2];
            match ($match[1]) {
                't' => $style->setTopMargin($value),
                'b' => $style->setBottomMargin($value),
                'l' => $style->setLeftMargin($value),
                'r' => $style->setRightMargin($value),
                'x' => $style->setXMargins($value),
                'y' => $style->setYMargins($value),
                default => $style->setMargins($value) // all
            };
        }

        return $this;
    }

    /**
     * Update this style, depending on the tag name and class.
     */
    private function updateStyle(): static
    {
        // create style by tag name
        $style = HtmlStyleFactory::create($this->name);
        if (!$style instanceof HtmlStyle) {
            return $this->setStyle(null);
        }

        // class
        if ($this->className) {
            $classNames = \explode(' ', $this->className);
            foreach ($classNames as $class) {
                $this->parseBookmark($class)
                    ->parseFont($style, $class)
                    ->parseColor($style, $class)
                    ->parseMargins($style, $class)
                    ->parseBorders($style, $class)
                    ->parseAlignment($style, $class);
            }
        }

        return $this->setStyle($style);
    }
}
