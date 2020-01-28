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
 * Color used color for all drawing operations (lines, rectangles and cell borders).
 *
 * @author Laurent Muller
 */
class PdfDrawColor extends PdfColor
{
    /**
     * {@inheritdoc}
     */
    public function apply(PdfDocument $doc): void
    {
        $doc->SetDrawColor($this->red, $this->green, $this->blue);
    }
}
