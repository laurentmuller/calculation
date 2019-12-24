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
 * Define a drawing line.
 *
 * @author Laurent Muller
 */
class PdfLine implements IPdfDocumentUpdater
{
    /**
     * The default line width (0.2mm).
     */
    public const DEFAULT_WIDTH = 0.2;

    /**
     * The line width.
     *
     * @var float
     */
    protected $width;

    /**
     * Constructor.
     *
     * @param float $width the line width
     */
    public function __construct(float $width = self::DEFAULT_WIDTH)
    {
        $this->width = $width;
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
     *
     * @return float
     */
    public function getWidth()
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
