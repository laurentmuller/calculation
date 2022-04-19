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
 * The PDF document unit enumeration.
 */
enum PdfDocumentUnit:string
{
    /*
     * The centimeter document unit.
     */
    case CENTIMETER = 'cm';
    /*
     * The inch document unit.
     */
    case INCH = 'in';
    /*
     * The millimeter document unit.
     */
    case MILLIMETER = 'mm';
    /*
     * The point document unit.
     */
    case POINT = 'pt';
}
