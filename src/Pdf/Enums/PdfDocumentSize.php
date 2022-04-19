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
 * The PDF document size enumeration.
 */
enum PdfDocumentSize:string
{
    /*
     * The A3 document size.
     */
    case A3 = 'A3';
    /*
     * The A4 document size.
     */
    case A4 = 'A4';
    /*
     * The A5 document size.
     */
    case A5 = 'A5';
    /*
     * The Legal document size.
     */
    case LEGAL = 'Legal';
    /*
     * The Letter document size.
     */
    case LETTER = 'Letter';
}
