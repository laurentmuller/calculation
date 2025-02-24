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

/**
 * The HTML style.
 */
class HtmlStyle extends PdfStyle
{
    /**
     * The alignment.
     */
    private PdfTextAlignment $alignment = PdfTextAlignment::LEFT;

    /**
     * The bottom margin.
     */
    private float $bottomMargin = 0;

    /**
     * The left margin.
     */
    private float $leftMargin = 0;

    /**
     * The right margin.
     */
    private float $rightMargin = 0;

    /**
     * The top margin.
     */
    private float $topMargin = 0;

    public function __construct()
    {
        parent::__construct();
        $this->setBorder(PdfBorder::none());
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
        return $this->bottomMargin;
    }

    /**
     * Gets the left margin.
     */
    public function getLeftMargin(): float
    {
        return $this->leftMargin;
    }

    /**
     * Gets the right margin.
     */
    public function getRightMargin(): float
    {
        return $this->rightMargin;
    }

    /**
     * Gets the top margin.
     */
    public function getTopMargin(): float
    {
        return $this->topMargin;
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
        $this->bottomMargin = $bottomMargin;

        return $this;
    }

    /**
     * Sets left margin.
     */
    public function setLeftMargin(float $leftMargin): self
    {
        $this->leftMargin = $leftMargin;

        return $this;
    }

    /**
     * Sets all four margins to the given value.
     */
    public function setMargins(float $margin): self
    {
        $this->topMargin = $margin;
        $this->bottomMargin = $margin;
        $this->leftMargin = $margin;
        $this->rightMargin = $margin;

        return $this;
    }

    /**
     * Sets the right margin.
     */
    public function setRightMargin(float $rightMargin): self
    {
        $this->rightMargin = $rightMargin;

        return $this;
    }

    /**
     * Sets top margin.
     */
    public function setTopMargin(float $topMargin): self
    {
        $this->topMargin = $topMargin;

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
        $alignment = match ($class) {
            'text-start' => PdfTextAlignment::LEFT,
            'text-end' => PdfTextAlignment::RIGHT,
            'text-center' => PdfTextAlignment::CENTER,
            'text-justify' => PdfTextAlignment::JUSTIFIED,
            default => null,
        };
        if (null !== $alignment) {
            $this->setAlignment($alignment);
        }

        return $this;
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
            'border-top-0' => PdfBorder::all()->setTop(false),
            'border-start-0' => PdfBorder::all()->setLeft(false),
            'border-end-0' => PdfBorder::all()->setRight(false),
            'border-bottom-0' => PdfBorder::all()->setBottom(false),
            default => null
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
        switch ($class) {
            case 'fst-normal':
                $this->setFontRegular();
                break;
            case 'fw-bold':
            case 'fw-bolder':
                $this->setFontBold(true);
                break;
            case 'fst-italic':
                $this->setFontItalic(true);
                break;
            case 'text-decoration-underline':
                $this->setFontUnderline(true);
                break;
            case 'font-monospace':
                $this->setFontName(PdfFontName::COURIER);
                break;
        }

        return $this;
    }

    private function updateMargins(string $class): self
    {
        $spacing = HtmlSpacing::instance($class);
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
