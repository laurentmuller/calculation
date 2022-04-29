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
 * The PDF image type enumeration.
 */
enum PdfImageType: string
{
    case GIF = 'gif';
    case JPEG = 'jpeg';
    case JPG = 'jpg';
    case PNG = 'png';
}
