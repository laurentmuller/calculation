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

use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\PdfBorder;
use App\Pdf\PdfStyle;

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
        $this->setBorder(PdfBorder::NONE);
    }

    /**
     * Sets the font style to bold.
     *
     * @param bool $add true to add bold to existing style, false to replace
     */
    public function bold(bool $add = false): self
    {
        $this->getFont()->bold($add);

        return $this;
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
}
