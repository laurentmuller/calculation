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

use App\Pdf\IPdfConstants;
use App\Pdf\PdfFont;
use App\Report\HtmlReport;
use App\Utils\Utils;

/**
 * Represents a HTML chunk.
 *
 * @author Laurent Muller
 */
abstract class HtmlChunk implements IHtmlConstants, IPdfConstants
{
    /**
     * The class name.
     *
     * @var string
     */
    protected $className;

    /**
     * The tag name.
     *
     * @var string
     */
    protected $name;

    /**
     * The parent chunk.
     *
     * @var HtmlParentChunk
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
     *
     * @return string
     */
    public function getName()
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
        return null !== $this->getStyle();
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
            $classNames = \explode(' ', $this->className);
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

                    default:
                        $this->parseMargins($style, $class);
                        break;
                }
            }
        }

        return $this->setStyle($style);
    }

    /**
     * Parses the margins class.
     *
     * @param HtmlStyle $style the style to update
     * @param string    $class the margins class name
     *
     * @return HtmlStyle the style
     */
    private function parseMargins(HtmlStyle $style, string $class): HtmlStyle
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

        return $style;
    }
}
