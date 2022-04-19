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
 * THE PDF document zoom enumeration.
 */
enum PdfDocumentZoom:string
{
    /*
     * Uses viewer default mode.
     */
    case DEFAULT = 'default';
    /*
     * Displays the entire page on screen.
     */
    case FULL_PAGE = 'fullpage';
    /*
     * Uses maximum width of window.
     */
    case FULL_WIDTH = 'fullwidth';
    /*
     * Uses real size (equivalent to 100% zoom).
     */
    case REAL = 'real';
}
