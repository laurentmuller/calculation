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
 * Trait for class implementing the <code>PdfCellListenerInterface</code> interface.
 *
 * By default, do nothing and returns always <code>false</code>.
 * Class can override only the desired methods.
 *
 * @author Laurent Muller
 *
 * @see PdfCellListenerInterface
 */
trait PdfCellListenerTrait
{
    /**
     * {@inheritdoc}
     *
     * @see PdfCellListenerInterface::onDrawCellBackground()
     */
    public function onDrawCellBackground(PdfTableBuilder $builder, int $index, PdfRectangle $bounds): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @see PdfCellListenerInterface::onDrawCellBorder()
     */
    public function onDrawCellBorder(PdfTableBuilder $builder, int $index, PdfRectangle $bounds, $border): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @see PdfCellListenerInterface::onDrawCellText()
     */
    public function onDrawCellText(PdfTableBuilder $builder, int $index, PdfRectangle $bounds, string $text, string $align, float $height): bool
    {
        return false;
    }
}
