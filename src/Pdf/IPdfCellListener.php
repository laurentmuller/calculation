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
 * Class implementing this interface deals with cell drawing functions.
 *
 * @author Laurent Muller
 */
interface IPdfCellListener
{
    /**
     * Called when a cell must be filled.
     *
     * @param PdfTableBuilder $builder the table builder
     * @param int             $index   the column index
     * @param PdfRectangle    $bounds  the cell bounds
     *
     * @return bool false to call the default behavior; true if listener handle the draw function
     */
    public function onDrawCellBackground(PdfTableBuilder $builder, int $index, PdfRectangle $bounds): bool;

    /**
     * Called when a cell border must be draw.
     *
     * @param PdfTableBuilder $builder the table builder
     * @param int             $index   the column index
     * @param PdfRectangle    $bounds  the cell bounds
     * @param mixed           $border  the border style
     *
     * @return bool false to call the default behavior; true if listener handle the draw function
     */
    public function onDrawCellBorder(PdfTableBuilder $builder, int $index, PdfRectangle $bounds, $border): bool;

    /**
     * Called when the text must be draw within the cell.
     *
     * @param PdfTableBuilder $builder the table builder
     * @param int             $index   the column index
     * @param PdfRectangle    $bounds  the cell bounds
     * @param string          $text    the cell text
     * @param string          $align   the text alignment
     * @param float           $height  the line height
     *
     * @return bool bool false to call the default behavior; true if listener handle the draw function
     */
    public function onDrawCellText(PdfTableBuilder $builder, int $index, PdfRectangle $bounds, string $text, string $align, float $height): bool;
}
