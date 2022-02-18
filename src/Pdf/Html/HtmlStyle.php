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

use App\Pdf\PdfAlignmentTrait;
use App\Pdf\PdfStyle;
use App\Util\Utils;

/**
 * The HTML style.
 *
 * @author Laurent Muller
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class HtmlStyle extends PdfStyle
{
    use PdfAlignmentTrait;

    /**
     * The bottom margin.
     */
    protected float $bottomMargin = 0;

    /**
     * The left margin.
     */
    protected float $leftMargin = 0;

    /**
     * The right margin.
     */
    protected float $rightMargin = 0;

    /**
     * The top margin.
     */
    protected float $topMargin = 0;

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $shortName = Utils::getShortName($this);

        return \sprintf('%s(%s, Margins(L:%g, R:%g, T:%g, B:%g))', $shortName, (string) $this->font, $this->leftMargin, $this->rightMargin, $this->topMargin, $this->bottomMargin);
    }

    /**
     * Sets the font style to bold.
     *
     * @param bool $add true to add bold to existing style, false to replace
     */
    public function bold(bool $add = false): self
    {
        $this->font->bold($add);

        return $this;
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
     * Sets the font style to italic.
     *
     * @param bool $add true to add italic to existing style, false to replace
     */
    public function italic(bool $add = false): self
    {
        $this->font->italic($add);

        return $this;
    }

    /**
     * Sets the font style to italic.
     */
    public function regular(): self
    {
        $this->font->regular();

        return $this;
    }

    /**
     * Reset all values to default.
     *
     * @return self
     */
    public function reset(): PdfStyle
    {
        parent::reset();

        return $this->resetMargins();
    }

    /**
     * Sets all four margins to 0.
     */
    public function resetMargins(): self
    {
        return $this->setMargins(0);
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
     * Sets the font style to underline.
     *
     * @param bool $add true to add bold to existing style, false to replace
     */
    public function underline(bool $add = false): self
    {
        $this->font->underline($add);

        return $this;
    }
}
