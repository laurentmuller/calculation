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
use App\Pdf\PdfDrawColor;
use App\Pdf\PdfFillColor;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTextColor;

/**
 * The HTML style.
 */
class HtmlStyle extends PdfStyle
{
    /**
     * The pattern to extract margins.
     */
    private const MARGINS_PATTERN = '/^[mp]([tbsexy])?-(sm-|md-|lg-|xl-|xxl-)?([012345])/im';

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
        $this->setBorder(PdfBorder::NONE);
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
    public function reset(): static
    {
        parent::reset();
        $this->resetMargins();
        $this->setAlignment(PdfTextAlignment::LEFT);

        return $this;
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
        return $this->setLeftMargin($margin)
            ->setRightMargin($margin)
            ->setTopMargin($margin)
            ->setBottomMargin($margin);
    }

    /**
     * Sets right margin.
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
     * Sets the left and right margins.
     */
    public function setXMargins(float $margins): self
    {
        return $this->setLeftMargin($margins)->setRightMargin($margins);
    }

    /**
     * Sets the top and bottom margins.
     */
    public function setYMargins(float $margins): self
    {
        return $this->setTopMargin($margins)->setBottomMargin($margins);
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
        return PdfDrawColor::create('#808080') ?? PdfDrawColor::default();
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
            'border' => PdfBorder::ALL,
            'border-top' => PdfBorder::TOP,
            'border-bottom' => PdfBorder::BOTTOM,
            'border-start' => PdfBorder::LEFT,
            'border-end' => PdfBorder::RIGHT,
            'border-0' => PdfBorder::NONE,
            'border-top-0' => PdfBorder::LEFT . PdfBorder::RIGHT . PdfBorder::BOTTOM,
            'border-start-0' => PdfBorder::RIGHT . PdfBorder::TOP . PdfBorder::BOTTOM,
            'border-end-0' => PdfBorder::LEFT . PdfBorder::TOP . PdfBorder::BOTTOM,
            'border-bottom-0' => PdfBorder::LEFT . PdfBorder::RIGHT . PdfBorder::TOP,
            default => null
        };
        if (null !== $border) {
            $this->setDrawColor($this->getDefaultBorderColor())
                ->setBorder($border);
        }

        return $this;
    }

    private function updateColor(string $class): self
    {
        // text
        $color = HtmlBootstrapColors::parseTextColor($class);
        if ($color instanceof PdfTextColor) {
            $this->setTextColor($color);
        }
        // background
        $color = HtmlBootstrapColors::parseFillColor($class);
        if ($color instanceof PdfFillColor) {
            $this->setFillColor($color);
        }
        // border
        $color = HtmlBootstrapColors::parseBorderColor($class);
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
        $matches = [];
        if (\preg_match_all(self::MARGINS_PATTERN, $class, $matches, \PREG_SET_ORDER)) {
            $match = $matches[0];
            $value = (float) $match[3];
            match ($match[1]) {
                't' => $this->setTopMargin($value),
                'b' => $this->setBottomMargin($value),
                's' => $this->setLeftMargin($value),
                'e' => $this->setRightMargin($value),
                'x' => $this->setXMargins($value),
                'y' => $this->setYMargins($value),
                default => $this->setMargins($value) // all
            };
        }

        return $this;
    }
}
