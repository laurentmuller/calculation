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
 * The PDF display layout enumeration.
 */
enum PdfDocumentLayout: string
{
    /*
     * Displays pages continuously.
     */
    case CONTINUOUS = 'continuous';
    /*
     * Uses viewer default mode.
     */
    case DEFAULT = 'default';
    /*
     * Displays one page at once.
     */
    case SINGLE = 'single';
    /*
     * Displays two pages on two columns.
     */
    case TWO_PAGES = 'two';
}
