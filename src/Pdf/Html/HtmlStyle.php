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

use App\Pdf\Colors\PdfDrawColor;
use App\Pdf\Colors\PdfFillColor;
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\PdfStyle;
use fpdf\Enums\PdfFontName;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfBorder;
use fpdf\PdfMargins;

/**
 * The HTML style.
 */
class HtmlStyle extends PdfStyle
{
    /** The alignment. */
    private PdfTextAlignment $alignment = PdfTextAlignment::LEFT;

    /** The margins. */
    private readonly PdfMargins $margins;

    public function __construct()
    {
        parent::__construct();
        $this->setBorder(PdfBorder::none());
        $this->margins = PdfMargins::instance();
    }

    /**
     * Gets the default style.
     *
     * The style has the following properties:
     *
     * - Font: Arial 9 points Regular.
     * - Line width: 0.2 mm.
     * - Fill color: White.
     * - Draw color: Black.
     * - Text color: Black.
     * - Border: None.
     * - Text Alignment: Left
     * - Margins: 0.0
     */
    #[\Override]
    public static function default(): self
    {
        return new self();
    }

    /**
     * Gets the alignment.
     */
    public function getAlignment(): PdfTextAlignment
    {
        return $this->alignment;
    }

    /**
     * Gets the bottom margin.
     */
    public function getBottomMargin(): float
    {
        return $this->margins->bottom;
    }

    /**
     * Gets the left margin.
     */
    public function getLeftMargin(): float
    {
        return $this->margins->left;
    }

    /**
     * Gets the right margin.
     */
    public function getRightMargin(): float
    {
        return $this->margins->right;
    }

    /**
     * Gets the top margin.
     */
    public function getTopMargin(): float
    {
        return $this->margins->top;
    }

    /**
     * Reset all values to default.
     */
    #[\Override]
    public function reset(): static
    {
        parent::reset();
        $this->resetAlignment()
            ->resetMargins();

        return $this;
    }

    /**
     * Sets alignement to default (left).
     */
    public function resetAlignment(): self
    {
        return $this->setAlignment(PdfTextAlignment::LEFT);
    }

    /**
     * Sets all four margins to 0.
     */
    public function resetMargins(): self
    {
        return $this->setMargins(0);
    }

    /**
     * Sets the alignment.
     */
    public function setAlignment(PdfTextAlignment $alignment): self
    {
        $this->alignment = $alignment;

        return $this;
    }

    /**
     * Sets bottom margin.
     */
    public function setBottomMargin(float $bottomMargin): self
    {
        $this->margins->bottom = $bottomMargin;

        return $this;
    }

    /**
     * Sets left margin.
     */
    public function setLeftMargin(float $leftMargin): self
    {
        $this->margins->left = $leftMargin;

        return $this;
    }

    /**
     * Sets all four margins to the given value.
     */
    public function setMargins(float $margin): self
    {
        $this->margins->setMargins($margin);

        return $this;
    }

    /**
     * Sets the right margin.
     */
    public function setRightMargin(float $rightMargin): self
    {
        $this->margins->right = $rightMargin;

        return $this;
    }

    /**
     * Sets top margin.
     */
    public function setTopMargin(float $topMargin): self
    {
        $this->margins->top = $topMargin;

        return $this;
    }

    /**
     * Update this style for the given class.
     */
    public function update(string $class): self
    {
        if ('' === $class) {
            return $this;
        }

        return $this->updateFont($class)
            ->updateColor($class)
            ->updateMargins($class)
            ->updateBorders($class)
            ->updateAlignment($class);
    }

    private function getDefaultBorderColor(): PdfDrawColor
    {
        return new PdfDrawColor(128, 128, 128);
    }

    private function updateAlignment(string $class): self
    {
        return match ($class) {
            'text-start' => $this->setAlignment(PdfTextAlignment::LEFT),
            'text-end' => $this->setAlignment(PdfTextAlignment::RIGHT),
            'text-center' => $this->setAlignment(PdfTextAlignment::CENTER),
            'text-justify' => $this->setAlignment(PdfTextAlignment::JUSTIFIED),
            default => $this,
        };
    }

    /**
     * Parses the border class.
     */
    private function updateBorders(string $class): self
    {
        $border = match ($class) {
            'border' => PdfBorder::all(),
            'border-top' => PdfBorder::top(),
            'border-bottom' => PdfBorder::bottom(),
            'border-start' => PdfBorder::left(),
            'border-end' => PdfBorder::right(),
            'border-0' => PdfBorder::none(),
            'border-top-0' => PdfBorder::notTop(),
            'border-start-0' => PdfBorder::notLeft(),
            'border-end-0' => PdfBorder::notRight(),
            'border-bottom-0' => PdfBorder::notBottom(),
            default => null,
        };
        if ($border instanceof PdfBorder) {
            $this->setDrawColor($this->getDefaultBorderColor())
                ->setBorder($border);
        }

        return $this;
    }

    private function updateColor(string $class): self
    {
        // text
        $color = HtmlBootstrapColor::parseTextColor($class);
        if ($color instanceof PdfTextColor) {
            $this->setTextColor($color);
        }
        // background
        $color = HtmlBootstrapColor::parseFillColor($class);
        if ($color instanceof PdfFillColor) {
            $this->setFillColor($color);
        }
        // border
        $color = HtmlBootstrapColor::parseDrawColor($class);
        if ($color instanceof PdfDrawColor) {
            $this->setDrawColor($color);
        }

        return $this;
    }

    private function updateFont(string $class): self
    {
        return match ($class) {
            'fw-normal',
            'fst-normal' => $this->resetFont(),
            'fw-bold',
            'fw-bolder' => $this->setFontBold(true),
            'fst-italic' => $this->setFontItalic(true),
            'text-decoration-underline' => $this->setFontUnderline(true),
            'text-decoration-none' => $this->setFontStyle($this->getFont()->getStyle()->removeUnderLine()),
            'font-monospace' => $this->setFontName(PdfFontName::COURIER),
            'fs-1' => $this->setFontSize(HtmlTag::H1->getFontSize()),
            'fs-2' => $this->setFontSize(HtmlTag::H2->getFontSize()),
            'fs-3' => $this->setFontSize(HtmlTag::H3->getFontSize()),
            'fs-4' => $this->setFontSize(HtmlTag::H4->getFontSize()),
            'fs-5' => $this->setFontSize(HtmlTag::H5->getFontSize()),
            'fs-6' => $this->setFontSize(HtmlTag::H6->getFontSize()),
            default => $this,
        };
    }

    private function updateMargins(string $class): self
    {
        $spacing = HtmlSpacing::parse($class);
        if (!$spacing instanceof HtmlSpacing || $spacing->isNone()) {
            return $this;
        }

        $size = (float) $spacing->size;
        if ($spacing->isAll()) {
            return $this->setMargins($size);
        }
        if ($spacing->top) {
            $this->setTopMargin($size);
        }
        if ($spacing->bottom) {
            $this->setBottomMargin($size);
        }
        if ($spacing->left) {
            $this->setLeftMargin($size);
        }
        if ($spacing->right) {
            $this->setRightMargin($size);
        }

        return $this;
    }
}
