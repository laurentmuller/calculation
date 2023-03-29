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

use App\Interfaces\DefaultEnumInterface;
use App\Traits\DefaultEnumTrait;
use Elao\Enum\Attribute\EnumCase;

/**
 * The PDF document unit enumeration.
 *
 * @implements DefaultEnumInterface<PdfDocumentUnit>
 */
enum PdfDocumentUnit: string implements DefaultEnumInterface
{
    use DefaultEnumTrait;

    /*
     * Centimeter.
     */
    case CENTIMETER = 'cm';

    /*
     * Inch.
     */
    case INCH = 'in';

    /*
     * Millimeter (default).
     */
    #[EnumCase(extras: ['default' => true])]
    case MILLIMETER = 'mm';

    /*
     * Point.
     */
    case POINT = 'pt';
}
