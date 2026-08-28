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

/**
 * The PDF cell image output position enumeration.
 *
 * @implements DefaultEnumInterface<PdfCellImagePosition>
 */
enum PdfCellImagePosition implements DefaultEnumInterface
{
    /** Output image before the text. */
    case LEFT;

    /** Output image after the text. */
    case RIGHT;

    /** The default enumeration. */
    public const self DEFAULT = self::LEFT;
}
