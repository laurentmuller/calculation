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
    /**
     * GIF format.
     */
    case GIF = 'gif';

    /**
     * JPEG format.
     */
    case JPEG = 'jpeg';

    /**
     * JPG format.
     */
    case JPG = 'jpg';

    /**
     * PNG format.
     */
    case PNG = 'png';
}
