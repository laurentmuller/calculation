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
use App\Service\ImageService;
use App\Traits\EnumDefaultTrait;
use Elao\Enum\Attribute\EnumCase;

/**
 * Image file extension numeration.
 *
 * @implements EnumDefaultInterface<ImageExtension>
 *
 * @psalm-type SaveOptionsType = array{
 *     compressed?: bool,
 *     quality?: int,
 *     filters?: int,
 *     foreground_color?: int}
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

    /**
     *The Wireless Application Protocol Bitmap Format ("wbmp").
     */
    case WBMP = 'wbmp';

    /**
     * The Webp file extension ("webp").
     */
    case WEBP = 'webp';
    /*
     * The XBM file extension ("xbm").
     */
    case XBM = 'xbm';

    /*
     * The XPM file extension ("xpm").
     */
    case XPM = 'xpm';

    /**
     * Create a new image from file or URL.
     *
     * @param string $filename the path to the image
     *
     * @return \GdImage|false an image resource identifier on success, false on error
     */
    public function createImage(string $filename): \GdImage|false
    {
        return match ($this) {
            ImageExtension::BMP => \imagecreatefrombmp($filename),
            ImageExtension::GIF => \imagecreatefromgif($filename),
            ImageExtension::JPEG,
            ImageExtension::JPG => \imagecreatefromjpeg($filename),
            ImageExtension::PNG => \imagecreatefrompng($filename),
            ImageExtension::WEBP => \imagecreatefromwebp($filename),
            ImageExtension::WBMP => \imagecreatefromwbmp($filename),
            ImageExtension::XBM => \imagecreatefromxbm($filename),
            ImageExtension::XPM => \imagecreatefromxpm($filename),
        };
    }

    /**
     * Output an image to either the browser or a file.
     *
     * @param \GdImage|ImageService   $image   a GdImage object, returned by one of the image creation functions or an
     *                                         image service to get GdImage for
     * @param resource|string|null    $file    The path or an open stream resource, which is automatically closed after
     *                                         this function returns; to save the file to. If not set or null, the raw
     *                                         image stream will be output directly.
     * @param array<string, int|bool> $options additional options to use
     *
     * @return bool true on success or false on failure
     *
     * @psalm-param SaveOptionsType $options
     */
    public function saveImage(\GdImage|ImageService $image, mixed $file = null, array $options = []): bool
    {
        if ($image instanceof ImageService) {
            $image = $image->getImage();
        }

        return match ($this) {
            ImageExtension::BMP => \imagebmp($image, $file, $options['compressed'] ?? true),
            ImageExtension::GIF => \imagegif($image, $file),
            ImageExtension::JPEG,
            ImageExtension::JPG => \imagejpeg($image, $file, $options['quality'] ?? -1),
            ImageExtension::PNG => \imagepng($image, $file, $options['quality'] ?? -1, $options['filters'] ?? -1),
            ImageExtension::WEBP => \imagewebp($image, $file, $options['quality'] ?? 80),
            ImageExtension::WBMP => \imagewbmp($image, $file, $options['foreground_color'] ?? null),
            ImageExtension::XBM => \imagexbm($image, (string) $file, $options['foreground_color'] ?? null),
            ImageExtension::XPM => false,
        };
    }
}
