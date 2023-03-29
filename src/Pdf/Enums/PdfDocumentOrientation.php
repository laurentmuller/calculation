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
 * The PDF document orientation enumeration.
 *
 * @implements DefaultEnumInterface<PdfDocumentOrientation>
 */
enum PdfDocumentOrientation: string implements DefaultEnumInterface
{
    use DefaultEnumTrait;

    /*
     * Landscape orientation.
     */
    case LANDSCAPE = 'L';

    /*
     * Portrait orientation (default).
     */
    #[EnumCase(extras: ['default' => true])]
    case PORTRAIT = 'P';
}
