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

/**
 * Color used for drawing text operations.
 *
 * @author Laurent Muller
 */
class PdfTextColor extends PdfColor
{
    /**
     * Constructor.
     * All values must be bewtween 0 to 255 inclusive.
     *
     * @param int $red   the red component
     * @param int $green the green component
     * @param int $blue  the blue component
     */
    final public function __construct(int $red = 0, int $green = 0, int $blue = 0)
    {
        parent::__construct($red, $green, $blue);
    }

    /**
     * {@inheritdoc}
     */
    public function apply(PdfDocument $doc): void
    {
        $doc->SetTextColor($this->red, $this->green, $this->blue);
    }
}
