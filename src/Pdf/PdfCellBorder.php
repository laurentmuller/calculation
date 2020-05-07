<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Pdf;

use App\Utils\Utils;

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
     *
     * @var PdfDrawColor
     */
    protected $color;

    /**
     * The line width.
     *
     * @var PdfLine
     */
    protected $line;

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

        return \sprintf('%s(%s, %s)', $name, $this->color, $this->line);
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
