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

namespace App\Pdf;

use App\Pdf\Interfaces\PdfDocumentUpdaterInterface;
use fpdf\PdfDocument;

/**
 * Define a drawing line.
 */
class PdfLine implements PdfDocumentUpdaterInterface
{
    /**
     * The default line width (0.2 mm).
     */
    final public const DEFAULT_WIDTH = 0.2;

    /**
     * @param float $width the line width
     */
    public function __construct(private float $width = self::DEFAULT_WIDTH)
    {
    }

    #[\Override]
    public function apply(PdfDocument $doc): void
    {
        $doc->setLineWidth($this->width);
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
