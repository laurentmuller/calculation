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
 * Define a drawing line.
 *
 * @author Laurent Muller
 */
class PdfLine implements PdfDocumentUpdaterInterface, \Stringable
{
    /**
     * The default line width (0.2mm).
     */
    final public const DEFAULT_WIDTH = 0.2;

    /**
     * Constructor.
     *
     * @param float $width the line width
     */
    public function __construct(protected float $width = self::DEFAULT_WIDTH)
    {
    }

    public function __toString(): string
    {
        $name = Utils::getShortName($this);

        return \sprintf('%s(%g)', $name, $this->width);
    }

    /**
     * {@inheritdoc}
     */
    public function apply(PdfDocument $doc): void
    {
        $doc->SetLineWidth($this->width);
    }

    /**
     * Creates a new instance.
     *
     * @param float $width the line width
     */
    public static function create(float $width = self::DEFAULT_WIDTH): self
    {
        return new self($width);
    }

    /**
     * Gets the default line.
     *
     * @return PdfLine the default line
     */
    public static function default(): self
    {
        return new self();
    }

    /**
     * Gets the width.
     */
    public function getWidth(): float
    {
        return $this->width;
    }

    /**
     * Sets the width.
     */
    public function setWidth(float $width): self
    {
        $this->width = $width;

        return $this;
    }
}
