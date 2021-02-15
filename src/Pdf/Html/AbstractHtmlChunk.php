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

use App\Pdf\PdfConstantsInterface;
use App\Pdf\PdfFillColor;
use App\Pdf\PdfFont;
use App\Pdf\PdfTextColor;
use App\Report\HtmlReport;
use App\Util\Utils;

/**
 * Represents a HTML chunk.
 *
 * @author Laurent Muller
 */
abstract class AbstractHtmlChunk implements HtmlConstantsInterface, PdfConstantsInterface
{
    /**
     * The class name.
     *
     * @var string|null
     */
    protected $className;

    /**
     * The css style.
     *
     * @var string|null
     */
    protected $css;

    /**
     * The tag name.
     *
     * @var string
     */
    protected $name;

    /**
     * The parent chunk.
     *
     * @var ?HtmlParentChunk
     */
    protected $parent;

    /**
     * The style.
     *
     * @var HtmlStyle
     */
    protected $style;

    /**
     * Constructor.
     *
     * @param string          $name   the tag name
     * @param HtmlParentChunk $parent the parent chunk
     */
    public function __construct(string $name, ?HtmlParentChunk $parent = null)
    {
        // copy
        $this->name = $name;

        // add to parent
        if ($parent) {
            $parent->add($this);
        }

        // style
        $this->updateStyle();
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $shortName = Utils::getShortName($this);
        if ($this->className) {
            return \sprintf("%s(%s, '%s')", $shortName, $this->name, $this->className);
        }

        return \sprintf('%s(%s)', $shortName, $this->name);
    }

    /**
     * Apply this style (if any) to the given report.
     *
     * @param HtmlReport $report the report to update
     */
    public function applyStyle(HtmlReport $report): self
    {
        if ($this->hasStyle()) {
            $this->getStyle()->apply($report);
        }

        return $this;
    }

    /**
     * Finds the parent for the given the tag names.
     *
     * @param string[] ...$names the tag names to search for
     *
     * @return HtmlParentChunk|null the parent, if found; <code>null</code> otherwise
     */
    public function findParent(string ...$names): ?HtmlParentChunk
    {
        /** @var HtmlParentChunk $parent */
        $parent = $this->parent;
        while (null !== $parent && !$parent->is(...$names)) {
            $parent = $parent->getParent();
        }

        return $parent;
    }

    /**
     * Gets the text alignment from this style or left, if none.
     *
     * @return string the text alignment
     */
    public function getAlignment(): string
    {
        if ($this->hasStyle()) {
            return $this->style->getAlignment();
        }

        return self::ALIGN_LEFT;
    }

    /**
     * Gets the bottom margin from this style or 0 if none.
     *
     * @return float the bottom margin
     */
    public function getBottomMargin(): float
    {
        if ($this->hasStyle()) {
            return $this->style->getBottomMargin();
        }

        return 0;
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
     *
     * @return float the left margin
     */
    public function getLeftMargin(): float
    {
        if ($this->hasStyle()) {
            return $this->style->getLeftMargin();
        }

        return 0;
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
     *
     * @return \App\Pdf\Html\HtmlParentChunk|null
     */
    public function getParent(): ?HtmlParentChunk
    {
        return $this->parent;
    }

    /**
     * Gets the right margin from this style or 0 if none.
     *
     * @return float the right margin
     */
    public function getRightMargin(): float
    {
        if ($this->hasStyle()) {
            return $this->style->getRightMargin();
        }

        return 0;
    }

    /**
     * Gets the style.
     *
     * @return \App\Pdf\Html\HtmlStyle|null
     */
    public function getStyle(): ?HtmlStyle
    {
        return $this->style;
    }

    /**
     * Gets the top margin from this style or 0 if none.
     *
     * @return float the top margin
     */
    public function getTopMargin(): float
    {
        if ($this->hasStyle()) {
            return $this->style->getTopMargin();
        }

        return 0;
    }

    /**
     * Returns if a stlye is defined.
     *
     * @return bool true if a style is defined
     */
    public function hasStyle(): bool
    {
        return null !== $this->style;
    }

    /**
     * Gets index of the this chunk.
     *
     * @return int the index; -1 if root
     */
    public function index(): int
    {
        if ($this->parent) {
            return $this->parent->indexOf($this);
        }

        return -1;
    }

    /**
     * Returns if this tag name match the given one of the lis of names.
     *
     * @param string[] ...$names the tag names to check
     *
     * @return bool true if match
     */
    public function is(string ...$names): bool
    {
        foreach ($names as $name) {
            if (0 === \strcasecmp($this->name, $name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns if a new line must added at the end of the report output.
     *
     * @return bool true if a new line must added
     */
    public function isNewLine(): bool
    {
        return false;
    }

    /**
     * Output this chunk to the given report.
     *
     * @param HtmlReport $report the report to write to
     */
    public function output(HtmlReport $report): void
    {
        // apply style
        $this->applyStyle($report);

        // write text
        $text = $this->getOutputText();
        if (Utils::isString($text)) {
            $this->outputText($report, $text);
        }
    }

    /**
     * Sets the class name.
     */
    public function setClassName(?string $className): self
    {
        // clear
        $this->className = null;

        //check names
        if ($className) {
            $names = \explode(' ', \strtolower($className));
            $className = \array_reduce($names, function (string $carry, string $name) {
                if (!empty($name = \trim($name)) && false === \strpos($carry, $name)) {
                    return \trim($carry . ' ' . $name);
                }

                return $carry;
            }, '');

            if (!empty($className)) {
                $this->className = $className;
            }
        }

        return $this->updateStyle();
    }

    /**
     * Sets the CSS style.
     */
    public function setCss(?string $css): self
    {
        $this->css = $css;

        return $this;
    }

    /**
     * Sets the style.
     *
     * @param \App\Pdf\Html\HtmlStyle|null $style
     */
    public function setStyle(?HtmlStyle $style): self
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
     * @param HtmlReport   $report   the report to set and restore font
     * @param PdfFont|null $font     the font to apply
     * @param callable     $callback the callback to call after the font has been set. The report is passed as argument.
     */
    protected function applyFont(HtmlReport $report, ?PdfFont $font, callable $callback): void
    {
        if ($font) {
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
     */
    protected function applyMargins(HtmlReport $report, float $leftMargin, float $rightMargin, callable $callback): void
    {
        // get margins
        $oldLeft = $report->getLeftMargin();
        $oldRight = $report->getRightMargin();
        $newLeft = $oldLeft + $leftMargin;
        $newRight = $oldRight + $rightMargin;

        // apply new margins
        if ($newLeft !== $oldLeft) {
            $report->updateLeftMargin($newLeft);
        }
        if ($newRight !== $oldRight) {
            $report->updateRightMargin($newRight);
        }

        // call function
        $callback($report);

        // restore old margins
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
     *
     * @param HtmlReport $report the report to write to
     * @param string     $text   the text to output
     */
    protected function outputText(HtmlReport $report, string $text): void
    {
        $height = \max($report->getFontSize(), self::LINE_HEIGHT);
        $report->Write($height, $text);
    }

    /**
     * Parses the border class.
     *
     * @param HtmlStyle $style the style to update
     * @param string    $class the border class name
     */
    protected function parseBorders(HtmlStyle &$style, string $class): void
    {
        switch ($class) {
            case 'border':
                $style->setBorder(self::BORDER_ALL);
                break;

            case 'border-top':
                $style->setBorder(self::BORDER_TOP);
                break;

            case 'border-right':
                $style->setBorder(self::BORDER_RIGHT);
                break;

            case 'border-bottom':
                $style->setBorder(self::BORDER_BOTTOM);
                break;

            case 'border-left':
                $style->setBorder(self::BORDER_LEFT);
                break;

            case 'border-0':
                $style->setBorder(self::BORDER_NONE);
                break;

            case 'border-top-0':
                break;
            case 'border-right-0':
                break;
            case 'border-bottom-0':
                break;
            case 'border-left-0':
                break;
        }
    }

    /**
     * Parses the margins class.
     *
     * @param HtmlStyle $style the style to update
     * @param string    $class the margins class name
     */
    protected function parseMargins(HtmlStyle &$style, string $class): void
    {
        $pattern = '/m[tblrxy]{0,1}-[012345]/';
        if (\preg_match($pattern, $class)) {
            $value = (float) $class[-1];
            switch ($class[1]) {
                case 't':
                    $style->setTopMargin($value);
                    break;
                case 'b':
                    $style->setBottomMargin($value);
                    break;
                case 'l':
                    $style->setLeftMargin($value);
                    break;
                case 'r':
                    $style->setRightMargin($value);
                    break;
                case 'x':
                    $style->setXMargins($value);
                    break;
                case 'y':
                    $style->setYMargins($value);
                    break;
                default: // '-' = all
                    $style->setMargins($value);
                    break;
            }
        }
    }

    /**
     * Sets the parent.
     *
     * @param \App\Pdf\Html\HtmlParentChunk|null $parent
     */
    protected function setParent(?HtmlParentChunk $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Update this style, depending of the CSS.
     */
    protected function updateCss(): self
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
                            if ($color) {
                                $style->setTextColor($color);
                                $update = true;
                            }
                            break;

                        case 'background-color':
                            $color = PdfFillColor::create($value);
                            if ($color) {
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

    /**
     * Update this style, depending of the tag name and class.
     */
    protected function updateStyle(): self
    {
        // create style by tag name
        $style = HtmlStyleFactory::create($this->name);
        if (!$style) {
            return $this->setStyle(null);
        }

        // class
        if ($this->className) {
            $classNames = \preg_split('/\s+/m', $this->className);
            foreach ($classNames as $class) {
                switch ($class) {
                    case 'text-left':
                        $style->setAlignment(self::ALIGN_LEFT);
                        break;

                    case 'text-right':
                        $style->setAlignment(self::ALIGN_RIGHT);
                        break;

                    case 'text-center':
                        $style->setAlignment(self::ALIGN_CENTER);
                        break;

                    case 'text-justify':
                        $style->setAlignment(self::ALIGN_JUSTIFIED);
                        break;

                    case 'font-weight-bold':
                        $style->bold(true);
                        break;

                    case 'font-italic':
                        $style->italic(true);
                        break;

                    case 'font-weight-normal':
                        $style->regular();
                        break;

                    case 'text-monospace':
                        $style->regular()->getFont()->setName(PdfFont::NAME_COURIER);
                        break;

                    case 'text-primary':
                        $style->setTextColor(PdfTextColor::create(HtmlBootstrapColors::PRIMARY));
                        break;

                    case 'text-secondary':
                        $style->setTextColor(PdfTextColor::create(HtmlBootstrapColors::SECONDARY));
                        break;

                    case 'text-success':
                        $style->setTextColor(PdfTextColor::create(HtmlBootstrapColors::SUCCESS));
                        break;

                    case 'text-danger':
                        $style->setTextColor(PdfTextColor::create(HtmlBootstrapColors::DANGER));
                        break;

                    case 'text-warning':
                        $style->setTextColor(PdfTextColor::create(HtmlBootstrapColors::WARNING));
                        break;

                    case 'text-info':
                        $style->setTextColor(PdfTextColor::create(HtmlBootstrapColors::INFO));
                        break;

                    default:
                        $this->parseMargins($style, $class);
                        break;
                }
            }
        }

        return $this->setStyle($style);
    }
}
