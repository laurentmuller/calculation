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

/**
 * PDF constants.
 *
 * @author Laurent Muller
 */
interface PdfConstantsInterface
{
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
