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

/**
 * The HTML margins.
 */
class HtmlMargins
{
    /**
     * The bottom margin.
     */
    protected float $bottom = 0.0;

    /**
     * The left margin.
     */
    protected float $left = 0.0;

    /**
     * The right margin.
     */
    protected float $right = 0.0;

    /**
     * The top margin.
     */
    protected float $top = 0.0;

    /**
     * Gets the default margins.
     */
    public static function default(): self
    {
        return new self();
    }

    /**
     * Gets the bottom margin.
     */
    public function getBottom(): float
    {
        return $this->bottom;
    }

    /**
     * Gets the left margin.
     */
    public function getLeft(): float
    {
        return $this->left;
    }

    /**
     * Gets the right margin.
     */
    public function getRight(): float
    {
        return $this->right;
    }

    /**
     * Gets the top margin.
     */
    public function getTop(): float
    {
        return $this->top;
    }

    /**
     * Sets all four margins to 0.
     */
    public function reset(): self
    {
        return $this->setMargins(0);
    }

    /**
     * Sets bottom margin.
     */
    public function setBottom(float $bottom): self
    {
        $this->bottom = $bottom;

        return $this;
    }

    /**
     * Sets left margin.
     */
    public function setLeft(float $left): self
    {
        $this->left = $left;

        return $this;
    }

    /**
     * Sets all four margins to the given value.
     */
    public function setMargins(float $margin): self
    {
        return $this->setLeft($margin)->setRight($margin)
            ->setTop($margin)->setBottom($margin);
    }

    /**
     * Sets right margin.
     */
    public function setRight(float $right): self
    {
        $this->right = $right;

        return $this;
    }

    /**
     * Sets top margin.
     */
    public function setTop(float $top): self
    {
        $this->top = $top;

        return $this;
    }
}
