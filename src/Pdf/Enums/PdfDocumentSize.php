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
 * The PDF document size enumeration.
 *
 * @implements DefaultEnumInterface<PdfDocumentSize>
 */
enum PdfDocumentSize: string implements DefaultEnumInterface
{
    use DefaultEnumTrait;

    /*
     * A3 (297 × 420 mm).
     */
    case A3 = 'A3';

    /*
     * A4 (210 × 297 mm).
     *
     * This is the default value.
     */
    #[EnumCase(extras: ['default' => true])]
    case A4 = 'A4';

    /*
     * A5 (148 × 210 mm).
     */
    case A5 = 'A5';

    /*
     * Legal (8.5 x 14 inches))
     */
    case LEGAL = 'Legal';

    /*
     * Letter (8.5 x 11 inches).
     */
    case LETTER = 'Letter';
}
