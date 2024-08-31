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

namespace App\Enums;

use Elao\Enum\Attribute\EnumCase;
use fpdf\Interfaces\PdfEnumDefaultInterface;
use fpdf\Traits\PdfEnumDefaultTrait;

/**
 * Image size enumeration.
 *
 * @implements PdfEnumDefaultInterface<ImageSize>
 */
enum ImageSize: int implements PdfEnumDefaultInterface
{
    use PdfEnumDefaultTrait;

    /**
     * The default image size used for edit purpose (192 pixels).
     */
    #[EnumCase(extras: [PdfEnumDefaultInterface::NAME => true])]
    case DEFAULT = 192;

    /**
     * The medium image size used for user table (96 pixels).
     */
    case MEDIUM = 96;

    /**
     * The small image size used for logged user (32 pixels).
     */
    case SMALL = 32;
}
