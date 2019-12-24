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
 * PDF constants.
 *
 * @author Laurent Muller
 */
interface IPdfConstants
{
    /**
     * Center alignment.
     */
    public const ALIGN_CENTER = 'C';

    /**
     * Inherited alignment.
     */
    public const ALIGN_INHERITED = '';

    /**
     * Justified alignment.
     */
    public const ALIGN_JUSTIFIED = 'J';

    /**
     * Left alignment.
     */
    public const ALIGN_LEFT = 'L';

    /**
     * Right alignment.
     */
    public const ALIGN_RIGHT = 'R';

    /**
     * Draw a border on all four sides.
     */
    public const BORDER_ALL = 1;

    /**
     * Draw a border on the bottom side.
     */
    public const BORDER_BOTTOM = 'B';

    /**
     * Inherited border.
     */
    public const BORDER_INHERITED = -1;

    /**
     * Draw a border on the left side.
     */
    public const BORDER_LEFT = 'L';

    /**
     * No border is draw.
     */
    public const BORDER_NONE = 0;

    /**
     * Draw a border on the right side.
     */
    public const BORDER_RIGHT = 'R';

    /**
     * Draw a border on the top side.
     */
    public const BORDER_TOP = 'T';

    /**
     * The default line height.
     */
    public const LINE_HEIGHT = 5;

    /**
     * Move below of the printed cell.
     */
    public const MOVE_BELOW = 2;

    /**
     * Move at the beginning of the next line after the cell is printed.
     * it is equivalent to the setting <b>MOVE_RIGHT</b> and calling the
     * <code>PdfDocument->Ln()</code> method immediately afterwards.
     */
    public const MOVE_TO_NEW_LINE = 1;

    /**
     * Move to the right position of the printed cell.
     */
    public const MOVE_TO_RIGHT = 0;

    /**
     * The new line separator.
     */
    public const NEW_LINE = "\n";

    /**
     * Draw the border around the rectangle.
     */
    public const RECT_BORDER = 'D';

    /**
     * Draw the border and fill the rectangle.
     */
    public const RECT_BOTH = 'FD';

    /**
     * Fill the rectangle.
     */
    public const RECT_FILL = 'F';
}
