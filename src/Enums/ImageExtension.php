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
 *     foreground_color?: int|null}
 * @psalm-type AllowedOptionsType = array{
 *     compressed?: true,
 *     filters?: int,
 *     foreground_color?: null,
 *     quality?: int}
 */
enum ImageExtension: string implements EnumDefaultInterface
{
    use EnumDefaultTrait;

    /**
     * The Device-Independent Bitmap (DIB) graphic extension.
     */
    case BMP = 'bmp';

    /**
     * The Graphical Interchange Format (GIF) extension.
     */
    case GIF = 'gif';

    /**
     * The Joint Photographic Experts Group (JPEG) extension.
     */
    case JPEG = 'jpeg';

    /**
     * The Joint Photographic Group (JPG) extension.
     */
    case JPG = 'jpg';

    /**
     * The Portable Network Graphics (PNG) extension (default value).
     */
    #[EnumCase(extras: [EnumDefaultInterface::NAME => true])]
    case PNG = 'png';

    /**
     *The Wireless Application Protocol Bitmap Format.
     */
    case WBMP = 'wbmp';

    /**
     * The Webp extension.
     */
    case WEBP = 'webp';

    /**
     * The X Window System bitmap (XBM) extension.
     */
    case XBM = 'xbm';

    /**
     * The X11 pixmap extension (XPM).
     */
    case XPM = 'xpm';

    /**
     * Create a new image from a file or a URL.
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
     * Gets the allowed options to save an image.
     *
     * @psalm-return AllowedOptionsType
     */
    public function getAllowedOptions(): array
    {
        return match ($this) {
            ImageExtension::BMP => ['compressed' => true],
            ImageExtension::JPEG,
            ImageExtension::JPG,
            ImageExtension::WEBP => ['quality' => 80],
            ImageExtension::PNG => ['quality' => -1, 'filters' => -1],
            ImageExtension::WBMP,
            ImageExtension::XBM => ['foreground_color' => null],
            default => [],
        };
    }

    /**
     * Get the pattern for the finder.
     */
    public function getFilter(): string
    {
        return \sprintf('*.%s', $this->value);
    }

    /**
     * Gets the image type.
     *
     * @return int the image type or 0 if unknown
     */
    public function getImageType(): int
    {
        return match ($this) {
            ImageExtension::BMP => \IMAGETYPE_BMP,
            ImageExtension::GIF => \IMAGETYPE_GIF,
            ImageExtension::JPEG,
            ImageExtension::JPG => \IMAGETYPE_JPEG,
            ImageExtension::PNG => \IMAGETYPE_PNG,
            ImageExtension::WEBP => \IMAGETYPE_WEBP,
            ImageExtension::WBMP => \IMAGETYPE_WBMP,
            ImageExtension::XBM => \IMAGETYPE_XBM,
            ImageExtension::XPM => \IMAGETYPE_UNKNOWN,
        };
    }

    /**
     * Output an image to either the browser or a file.
     *
     * @param \GdImage|ImageService   $image   a GdImage object, returned by one of the image creation functions or an
     *                                         image service to get GdImage for
     * @param resource|string|null    $file    The path or an open stream resource is automatically closed after
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

        $allowed = $this->getAllowedOptions();
        $keys = \array_keys($allowed);
        $diff = \array_diff(\array_keys($options), $keys);
        if ([] !== $diff) {
            throw new \RuntimeException(\sprintf('Invalid options: %s, allowed options: %s.', \implode(', ', $diff), \implode(', ', $keys)));
        }

        /** @psalm-var array{compressed: bool, quality: int, filters: int, foreground_color: int|null} $options */
        $options = \array_merge($allowed, $options);

        return match ($this) {
            ImageExtension::BMP => \imagebmp($image, $file, $options['compressed']),
            ImageExtension::GIF => \imagegif($image, $file),
            ImageExtension::JPEG,
            ImageExtension::JPG => \imagejpeg($image, $file, $options['quality']),
            ImageExtension::PNG => \imagepng($image, $file, $options['quality'], $options['filters']),
            ImageExtension::WEBP => \imagewebp($image, $file, $options['quality']),
            ImageExtension::WBMP => \imagewbmp($image, $file, $options['foreground_color']),
            ImageExtension::XBM => \imagexbm($image, (string) $file, $options['foreground_color']),
            ImageExtension::XPM => false,
        };
    }

    /**
     * Gets the image extension for the given image type.
     */
    public static function tryFromType(int $type): ?ImageExtension
    {
        return match ($type) {
            \IMAGETYPE_BMP => ImageExtension::BMP,
            \IMAGETYPE_GIF => ImageExtension::GIF,
            \IMAGETYPE_JPEG => ImageExtension::JPEG,
            \IMAGETYPE_PNG => ImageExtension::PNG,
            \IMAGETYPE_WEBP => ImageExtension::WEBP,
            \IMAGETYPE_WBMP => ImageExtension::WBMP,
            \IMAGETYPE_XBM => ImageExtension::XBM,
            default => null,
        };
    }
}
