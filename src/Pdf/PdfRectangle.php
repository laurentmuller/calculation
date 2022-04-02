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

namespace App\Pdf;

use App\Util\Utils;

/**
 * Represents a rectangle.
 *
 * @author Laurent Muller
 */
class PdfRectangle implements \Stringable
{
    /**
     * Constructor.
     *
     * @param float $x      the x coordinate
     * @param float $y      the y coordinate
     * @param float $width  the width
     * @param float $height the height
     */
    public function __construct(protected float $x, protected float $y, protected float $width, protected float $height)
    {
    }

    public function __toString(): string
    {
        $name = Utils::getShortName($this);

        return \sprintf('%s(%g, %g, %g, %g)', $name, $this->x, $this->y, $this->width, $this->height);
    }

    /**
     * Gets the bottom coordinate.
     */
    public function bottom(): float
    {
        return $this->y + $this->height;
    }

    /**
     * Determines if the specified point is contained within this rectangle.
     *
     * @param float $x the x coordinate of the point to test
     * @param float $y the y coordinate of the point to test
     *
     * @return bool true if the point is contained within this rectangle; false otherwise
     */
    public function contains(float $x, float $y): bool
    {
        return $x >= $this->x
            && $y >= $this->y
            && $x < $this->right()
            && $y < $this->bottom();
    }

    /**
     * Gets the height.
     */
    public function height(): float
    {
        return $this->height;
    }

    /**
     * Sets the left indent. Do nothing if the indent is smaller than or equal to 0.
     *
     * @param int $indent the indent
     */
    public function indent(int $indent): self
    {
        if ($indent > 0) {
            $this->x += $indent;
            $this->width -= $indent;
        }

        return $this;
    }

    /**
     * Enlarges this rectangle by the specified amount.
     *
     * @param float $value the amount to inflate horizontally and vertically
     *
     * @return self this instance
     */
    public function inflate(float $value): self
    {
        return $this->inflateXY($value, $value);
    }

    /**
     * Enlarges this rectangle horizontally by the specified amount.
     *
     * @param float $value the amount to inflate horizontally
     *
     * @return self this instance
     */
    public function inflateX(float $value): self
    {
        return $this->inflateXY($value, 0);
    }

    /**
     * Enlarges this rectangle by the specified amount.
     *
     * @param float $x the amount to inflate horizontally
     * @param float $y the amount to inflate vertically
     *
     * @return self this instance
     */
    public function inflateXY(float $x, float $y): self
    {
        $this->x -= $x;
        $this->y -= $y;
        $this->width += 2 * $x;
        $this->height += 2 * $y;

        return $this;
    }

    /**
     * Enlarges this rectangle vertically by the specified amount.
     *
     * @param float $value the amount to inflate vertically
     *
     * @return self this instance
     */
    public function inflateY(float $value): self
    {
        return $this->inflateXY(0, $value);
    }

    /**
     * Determines if this rectangle intersects with the other rectangle.
     *
     * @param PdfRectangle $other the rectangle to test
     *
     * @return bool true if there is any intersection, false otherwise
     */
    public function intersect(self $other): bool
    {
        return ($other->x < $this->right())
            && ($other->y < $this->bottom())
            && ($other->right() > $this->x)
            && ($other->bottom() > $this->y);
    }

    /**
     * Gets the right coordinate.
     */
    public function right(): float
    {
        return $this->x + $this->width;
    }

    /**
     * Sets the bottom.
     *
     * @return self this instance
     */
    public function setBottom(float $bottom): self
    {
        return $this->setHeight($bottom - $this->y);
    }

    /**
     * Sets the height.
     *
     * @return self this instance
     */
    public function setHeight(float $height): self
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Sets the right.
     *
     * @return self this instance
     */
    public function setRight(float $right): self
    {
        return $this->setWidth($right - $this->x);
    }

    /**
     * Sets the width.
     *
     * @return self this instance
     */
    public function setWidth(float $width): self
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Sets the x coordinate.
     *
     * @return self this instance
     */
    public function setX(float $x): self
    {
        $this->x = $x;

        return $this;
    }

    /**
     * Sets the y coordinate.
     *
     * @return self this instance
     */
    public function setY(float $y): self
    {
        $this->y = $y;

        return $this;
    }

    /**
     * Gets a rectangle that contains the union of this and the other rectangle.
     *
     * @param PdfRectangle $other the rectangle to union
     *
     * @return PdfRectangle a rectangle that bounds the union of this and the other rectangle
     */
    public function union(self $other): self
    {
        $x = \min($this->x, $other->x);
        $y = \min($this->y, $other->y);
        $right = \max($this->right(), $other->right());
        $bottom = \max($this->bottom(), $other->bottom());

        return new self($x, $y, $right - $x, $bottom - $y);
    }

    /**
     * Gets the width.
     */
    public function width(): float
    {
        return $this->width;
    }

    /**
     * Gets the x coordinate.
     */
    public function x(): float
    {
        return $this->x;
    }

    /**
     * Gets the y coordinate.
     */
    public function y(): float
    {
        return $this->y;
    }
}
