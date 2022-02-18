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
 * Define a cell border.
 *
 * This class can be used to define a style border for a cell or individually for each 4 sides.
 *
 * @author Laurent Muller
 */
class PdfCellBorder implements PdfDocumentUpdaterInterface
{
    /**
     * The border color.
     */
    protected PdfDrawColor $color;

    /**
     * The line width.
     */
    protected PdfLine $line;

    /**
     * Constructor.
     *
     * @param PdfDrawColor $color the color or null to use default (black)
     * @param PdfLine      $line  the line or null  to use default (0.2mm)
     */
    public function __construct(?PdfDrawColor $color = null, ?PdfLine $line = null)
    {
        $this->color = $color ?: PdfDrawColor::black();
        $this->line = $line ?: PdfLine::default();
    }

    /**
     * {@inheritdoc}
     */
    public function __clone()
    {
        // deep clone
        $this->color = clone $this->color;
        $this->line = clone $this->line;
    }

    public function __toString(): string
    {
        $name = Utils::getShortName($this);

        return \sprintf('%s(%s, %s)', $name, (string) $this->color, (string) $this->line);
    }

    /**
     * {@inheritdoc}
     */
    public function apply(PdfDocument $doc): void
    {
        $this->color->apply($doc);
        $this->line->apply($doc);
    }

    /**
     * Gets the color.
     *
     * @return \App\Pdf\PdfDrawColor
     */
    public function getColor(): PdfDrawColor
    {
        return $this->color;
    }

    /**
     * Gets the line.
     *
     * @return \App\Pdf\PdfLine
     */
    public function getLine(): PdfLine
    {
        return $this->line;
    }

    /**
     * Sets the color.
     *
     * @param \App\Pdf\PdfDrawColor $color
     *
     * @return self this instance
     */
    public function setColor(PdfDrawColor $color): self
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Sets the line.
     *
     * @param \App\Pdf\PdfLine $line
     *
     * @return self this instance
     */
    public function setLine(PdfLine $line): self
    {
        $this->line = $line;

        return $this;
    }
}
