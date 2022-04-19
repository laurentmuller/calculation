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
 * The PDF document orientation enumeration.
 */
enum PdfDocumentOrientation: string
{
    /*
     * The document orientation as landscape.
     */
    case LANDSCAPE = 'L';
    /*
     * The document orientation as portrait.
     */
    case PORTRAIT = 'P';
}
