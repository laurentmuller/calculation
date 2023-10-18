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

use App\Interfaces\EnumDefaultInterface;
use App\Traits\EnumDefaultTrait;
use Elao\Enum\Attribute\EnumCase;

/**
 * The PDF document unit enumeration.
 *
 * @implements EnumDefaultInterface<PdfDocumentUnit>
 */
enum PdfDocumentUnit: string implements EnumDefaultInterface
{
    use EnumDefaultTrait;

    /**
     * Centimeter.
     */
    case CENTIMETER = 'cm';

    /**
     * Inch.
     */
    case INCH = 'in';

    /**
     * Millimeter (default).
     */
    #[EnumCase(extras: [EnumDefaultInterface::NAME => true])]
    case MILLIMETER = 'mm';

    /**
     * Point.
     */
    case POINT = 'pt';
}
