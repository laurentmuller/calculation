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

namespace App\Pdf\Enums;

/**
 * The point style (shape) enumeration.
 */
enum PdfPointStyle
{
    /** The circle shape. */
    case CIRCLE;
    /** The cross-shape. */
    case CROSS;
    /** The cross-rotation shape. */
    case CROSS_ROTATION;
    /** The diamond shape. */
    case DIAMOND;
    /** The ellipse shape. */
    case ELLIPSE;
    /** The rectangle shape. */
    case RECTANGLE;
    /** The square shape. */
    case SQUARE;
    /** The triangle shape. */
    case TRIANGLE;
}
