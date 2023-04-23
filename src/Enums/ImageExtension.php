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

use App\Interfaces\EnumDefaultInterface;
use App\Traits\EnumDefaultTrait;
use Elao\Enum\Attribute\EnumCase;

/**
 * Image file extension numeration.
 *
 * @implements EnumDefaultInterface<ImageExtension>
 */
enum ImageExtension: string implements EnumDefaultInterface
{
    use EnumDefaultTrait;

    /*
     * The Bitmap file extension ("bmp").
     */
    case BMP = 'bmp';

    /*
     * The Gif file extension ("gif").
     */
    case GIF = 'gif';

    /*
     * The JPEG file extension ("jpeg").
     */
    case JPEG = 'jpeg';

    /*
     * The JPG file extension ("jpg").
     */
    case JPG = 'jpg';

    /*
     * The PNG file extension ("png").
     */
    #[EnumCase(extras: [EnumDefaultInterface::NAME => true])]
    case PNG = 'png';

    /*
     * The XBM file extension ("xbm").
     */
    case XBM = 'xbm';
}
