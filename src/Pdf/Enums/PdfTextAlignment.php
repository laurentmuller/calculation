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
 * The cell text alignment enumeration.
 */
enum PdfTextAlignment: string
{
    /*
     * Center alignment.
     */
    case CENTER = 'C';
    /*
     * Justified alignment (only valid when output multi-cell).
     */
    case JUSTIFIED = 'J';
    /*
     * Left alignment (default).
     */
    case LEFT = 'L';
    /*
     * Right alignment.
     */
    case RIGHT = 'R';
}
