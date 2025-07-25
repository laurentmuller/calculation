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

namespace App\Service;

use App\Enums\ImageExtension;
use App\Utils\FileUtils;

/**
 * Service to manipulate an image.
 *
 * The underlying image resource and allocated colors are automatically destroyed as soon
 * as there are no other references to this instance.
 */
class ImageService
{
    /**
     * The default image resolution (96) in the dot per each (DPI).
     */
    final public const DEFAULT_RESOLUTION = 96;

    /**
     * The allocated colors.
     *
     * @var int[]
     */
    private array $colors = [];

    /**
     * @param \GdImage $image    the image to handle
     * @param ?string  $filename the file name, the URL or null if none
     */
    private function __construct(private readonly \GdImage $image, private readonly ?string $filename = null)
    {
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        foreach ($this->colors as $color) {
            \imagecolordeallocate($this->image, $color);
        }
        \imagedestroy($this->image);
    }

    /**
     * Allocate a color for this image.
     *
     * The color is automatically de-allocated as soon as there are no other references to this instance.
     *
     * @param int $red   the value of the red component
     * @param int $green the value of the green component
     * @param int $blue  the value of the blue component
     *
     * @phpstan-param int<0, 255> $red
     * @phpstan-param int<0, 255> $green
     * @phpstan-param int<0, 255> $blue
     *
     * @return int|false the color identifier on success, false if the allocation failed
     */
    public function allocate(int $red, int $green, int $blue): int|false
    {
        $color = \imagecolorallocate($this->image, $red, $green, $blue);
        if (false !== $color) {
            $this->colors[] = $color;
        }

        return $color;
    }

    /**
     * Allocate a color for this image.
     *
     * The color is automatically de-allocated as soon as there are no other references to this instance.
     *
     * @param int $red   the value of the red component
     * @param int $green the value of the green component
     * @param int $blue  the value of the blue component
     * @param int $alpha a value between 0 and 127
     *
     * @phpstan-param int<0, 255> $red
     * @phpstan-param int<0, 255> $green
     * @phpstan-param int<0, 255> $blue
     * @phpstan-param int<0, 127> $alpha
     *
     * @return int|false the color identifier on success, false if the allocation failed
     */
    public function allocateAlpha(int $red = 0, int $green = 0, int $blue = 0, int $alpha = 127): int|false
    {
        $color = \imagecolorallocatealpha($this->image, $red, $green, $blue, $alpha);
        if (false !== $color) {
            $this->colors[] = $color;
        }

        return $color;
    }

    /**
     * Allocate the black color for this image.
     *
     * @return int|false the color identifier on success, false if the allocation failed
     */
    public function allocateBlack(): int|false
    {
        return $this->allocate(0, 0, 0);
    }

    /**
     * Allocate the white color for this image.
     *
     * @return int|false the color identifier on success, false if the allocation failed
     */
    public function allocateWhite(): int|false
    {
        return $this->allocate(255, 255, 255);
    }

    /**
     * Set the blending mode for this image.
     *
     * @param bool $blendMode whether to enable the blending mode or not
     *
     * @return bool true on success or false on failure
     */
    public function alphaBlending(bool $blendMode): bool
    {
        return \imagealphablending($this->image, $blendMode);
    }

    /**
     * Copy and resize part of an image with resampling.
     *
     * @param ImageService $dst_image the destination image handler
     * @param int          $dst_x     the x-coordinate of destination point
     * @param int          $dst_y     the y-coordinate of destination point
     * @param int          $src_x     the x-coordinate of source point
     * @param int          $src_y     the y-coordinate of source point
     * @param int          $dst_w     the destination width
     * @param int          $dst_h     the destination height
     * @param int          $src_w     the source width
     * @param int          $src_h     the source height
     *
     * @return bool true on success or false on failure
     */
    public function copyResampled(
        self $dst_image,
        int $dst_x,
        int $dst_y,
        int $src_x,
        int $src_y,
        int $dst_w,
        int $dst_h,
        int $src_w,
        int $src_h
    ): bool {
        return \imagecopyresampled(
            $dst_image->image,
            $this->image,
            $dst_x,
            $dst_y,
            $src_x,
            $src_y,
            $dst_w,
            $dst_h,
            $src_w,
            $src_h
        );
    }

    /**
     * Fill this image's bounds with the given color.
     *
     * @param int $color the fill color. A color identifier, created with the allocate method.
     * @param int $x     the x-coordinate of start point
     * @param int $y     the y-coordinate of start point
     *
     * @return bool true on success or false on failure
     *
     * @see ImageService::allocate()
     */
    public function fill(int $color, int $x = 0, int $y = 0): bool
    {
        return \imagefill($this->image, $x, $y, $color);
    }

    /**
     * Draw a filled rectangle with the give color.
     *
     * @param int $x      the x-coordinate
     * @param int $y      the y-coordinate
     * @param int $width  the rectangle width
     * @param int $height the rectangle height
     * @param int $color  the fill color. A color identifier, created with the allocate method.
     *
     * @return bool true on success or false on failure
     */
    public function fillRectangle(int $x, int $y, int $width, int $height, int $color): bool
    {
        return \imagefilledrectangle($this->image, $x, $y, $x + $width, $y + $height, $color);
    }

    /**
     * Create a new image handler from a file or a URL.
     *
     * This method uses the file extension to create the handler.
     *
     * @param string $filename the path to the image
     *
     * @return ?ImageService an image handler on success, <code>null</code> on error
     */
    public static function fromFile(string $filename): ?self
    {
        $file_extension = FileUtils::getExtension($filename, true);
        $image_extension = ImageExtension::tryFrom($file_extension);
        if (!$image_extension instanceof ImageExtension) {
            return null;
        }

        $image = $image_extension->createImage($filename);
        if (!$image instanceof \GdImage) {
            return null;
        }

        return new self($image, $filename);
    }

    /**
     * Create a new true color image handler.
     *
     * @param int $width  the image width
     * @param int $height the image height
     *
     * @phpstan-param positive-int $width
     * @phpstan-param positive-int $height
     *
     * @return ?ImageService an image handler on success, <code>null</code> on error
     */
    public static function fromTrueColor(int $width, int $height): ?self
    {
        $image = \imagecreatetruecolor($width, $height);
        if (!$image instanceof \GdImage) {
            return null;
        }

        return new self($image);
    }

    /**
     * Gets the loaded file name or URL, if any.
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * Gets the underlying image.
     */
    public function getImage(): \GdImage
    {
        return $this->image;
    }

    /**
     * Draw a line.
     *
     * @param int $x1    the x-coordinate for the first point
     * @param int $y1    the y-coordinate for the first point
     * @param int $x2    the x-coordinate for the second point
     * @param int $y2    the y-coordinate for the second point
     * @param int $color the line color. A color identifier, created with the allocate method.
     *
     * @return bool true on success or false on failure
     *
     * @see ImageService::allocate()
     */
    public function line(int $x1, int $y1, int $x2, int $y2, int $color): bool
    {
        return \imageline($this->image, $x1, $y1, $x2, $y2, $color);
    }

    /**
     * Draw a rectangle with the given border color.
     *
     * @param int $x      the x-coordinate
     * @param int $y      the y-coordinate
     * @param int $width  the rectangle width
     * @param int $height the rectangle height
     * @param int $color  the border color. A color identifier, created with the allocate method.
     *
     * @return bool true on success or false on failure
     */
    public function rectangle(int $x, int $y, int $width, int $height, int $color): bool
    {
        return \imagerectangle($this->image, $x, $y, $x + $width, $y + $height, $color);
    }

    /**
     * Get the horizontal resolution of this image in the dot per inch (DPI).
     *
     * @param int $default the default resolution to use on failure
     *
     * @return int the resolution
     */
    public function resolution(int $default = self::DEFAULT_RESOLUTION): int
    {
        /** @phpstan-var int[]|false $values */
        $values = \imageresolution($this->image);
        if (!\is_array($values)) {
            return $default;
        }

        return $values[0];
    }

    /**
     * Set the flag to save full alpha channel information (as opposed to single-color transparency)
     * when saving PNG images.
     *
     * @param bool $save whether to save the alpha channel or not
     *
     * @return bool true on success or false on failure
     */
    public function saveAlpha(bool $save): bool
    {
        return \imagesavealpha($this->image, $save);
    }

    /**
     * Set a single pixel.
     *
     * @param int $x     the x-coordinate
     * @param int $y     the y-coordinate
     * @param int $color a color identifier, created with the allocate method
     *
     * @return bool true on success or false on failure
     *
     * @see ImageService::allocate()
     */
    public function setPixel(int $x, int $y, int $color): bool
    {
        return \imagesetpixel($this->image, $x, $y, $color);
    }

    /**
     * Define a color as transparent.
     *
     * @param int $color a color identifier created with the allocate method
     *
     * @return int the identifier of the new (or current, if none is specified) transparent color is returned. If color
     *             is null, and the image has no transparent color, the returned identifier will be -1.
     */
    public function transparent(int $color): int
    {
        return \imagecolortransparent($this->image, $color);
    }

    /**
     * Gets the bounding box of a text using TrueType font.
     *
     * @param float  $size     the font size
     * @param float  $angle    the angle in degrees in which the text will be measured
     * @param string $fontFile the path to the TrueType font
     * @param string $text     the string to be measured
     *
     * @return int[]|false an array with 8 elements representing four points making the bounding box of
     *                     text on success and false on error.<br>
     *                     The points are relative to the text regardless of the angle, so "upper left" means in the
     *                     top left-hand corner seeing the text horizontally.<br><br>
     *                     <table class="table table-bordered" border="1" cellpadding="5" style="border-collapse: collapse;">
     *                     <thead>
     *                     <tr>
     *                     <th>Key</th>
     *                     <th>Content</th>
     *                     </tr>
     *                     </thead>
     *                     <tbody>
     *                     <tr>
     *                     <td style="text-align: center;">0</td>
     *                     <td>The lower left corner, X position.</td>
     *                     </tr>
     *                     <tr>
     *                     <td style="text-align: center;">1</td>
     *                     <td>The lower left corner, Y position.</td>
     *                     </tr>
     *                     <tr>
     *                     <td style="text-align: center;">2</td>
     *                     <td>The lower right corner, X position.</td>
     *                     </tr>
     *                     <tr>
     *                     <td style="text-align: center;">3</td>
     *                     <td>The lower right corner, Y position.</td>
     *                     </tr>
     *                     <tr>
     *                     <td style="text-align: center;">4</td>
     *                     <td>The upper right corner, X position.</td>
     *                     </tr>
     *                     <tr>
     *                     <td style="text-align: center;">5</td>
     *                     <td>The upper right corner, Y position.</td>
     *                     </tr>
     *                     <tr>
     *                     <td style="text-align: center;">6</td>
     *                     <td>The upper left corner, X position.</td>
     *                     </tr>
     *                     <tr>
     *                     <td style="text-align: center;">7</td>
     *                     <td>The upper left corner, Y position.</td>
     *                     </tr>
     *                     </tbody>
     *                     </table>
     */
    public function ttfBox(float $size, float $angle, string $fontFile, string $text): array|false
    {
        /** @phpstan-var int[]|false */
        return \imagettfbbox($size, $angle, $fontFile, $text);
    }

    /**
     * Gets the width and the height of a text using TrueType font.
     *
     * @param float  $size     the font size
     * @param float  $angle    the angle in degrees in which the text will be measured
     * @param string $fontFile the path to the TrueType font
     * @param string $text     the string to be measured
     *
     * @return int[] an array with the text width and the text height or an empty array ([0, 0]) on error
     *
     * @see ImageService::ttfBox()
     */
    public function ttfSize(float $size, float $angle, string $fontFile, string $text): array
    {
        $box = $this->ttfBox($size, $angle, $fontFile, $text);
        if (!\is_array($box)) {
            return [0, 0];
        }

        $values = [$box[0], $box[2], $box[4], $box[6]];
        $width = \max($values) - \min($values);
        $values = [$box[1], $box[3], $box[5], $box[7]];
        $height = \max($values) - \min($values);

        return [$width, $height];
    }

    /**
     * Write text to this image using TrueType font.
     *
     * @param float  $size     the font size
     * @param float  $angle    The angle in degrees, with 0 degrees being left-to-right reading the text.
     *                         Higher values represent a counter-clockwise rotation. For example, a
     *                         value of 90 would result in bottom-to-top reading text.
     * @param int    $x        The coordinates given by x and y will define the base point of the first
     *                         character (roughly the lower-left corner of the character)
     * @param int    $y        The y-coordinate. This sets the position of the font baseline, not
     *                         very bottom of the character.
     * @param int    $color    a color identifier created with the allocate method
     * @param string $fontFile the path to the TrueType font
     * @param string $text     The text string in UTF-8 encoding.
     *                         <br>
     *                         May include decimal numeric character references, of the form:
     *                         &amp;#8364;, to access characters in a font beyond position 127.
     *                         The hexadecimal format, like &amp;#xA9;, is supported.
     *                         Strings in UTF-8 encoding can be passed directly.
     *                         <br>
     *                         Named entities, such as &amp;copy;, are not supported. Consider using
     *                         html_entity_decode
     *                         to decode these named entities into UTF-8 strings.
     *                         <br>
     *                         If a character is used in the string which is not supported by the
     *                         font, a hollow rectangle will replace the character.
     *
     * @return array|false an array with 8 elements representing four points making the bounding box of
     *                     text on success and false on error.<br>
     *                     The points are relative to the text regardless of the angle, so "upper left" means in the
     *                     top left-hand corner seeing the text horizontally.<br>
     *                     <table class="table table-bordered" border="1" cellpadding="5" style="border-collapse: collapse;">
     *                     <thead>
     *                     <tr>
     *                     <th>Key</th>
     *                     <th>Content</th>
     *                     </tr>
     *                     </thead>
     *                     <tbody>
     *                     <tr>
     *                     <td style="text-align: center;">0</td>
     *                     <td>The lower left corner, X position.</td>
     *                     </tr>
     *                     <tr>
     *                     <td style="text-align: center;">1</td>
     *                     <td>The lower left corner, Y position.</td>
     *                     </tr>
     *                     <tr>
     *                     <td style="text-align: center;">2</td>
     *                     <td>The lower right corner, X position.</td>
     *                     </tr>
     *                     <tr>
     *                     <td style="text-align: center;">3</td>
     *                     <td>The lower right corner, Y position.</td>
     *                     </tr>
     *                     <tr>
     *                     <td style="text-align: center;">4</td>
     *                     <td>The upper right corner, X position.</td>
     *                     </tr>
     *                     <tr>
     *                     <td style="text-align: center;">5</td>
     *                     <td>The upper right corner, Y position.</td>
     *                     </tr>
     *                     <tr>
     *                     <td style="text-align: center;">6</td>
     *                     <td>The upper left corner, X position.</td>
     *                     </tr>
     *                     <tr>
     *                     <td style="text-align: center;">7</td>
     *                     <td>The upper left corner, Y position.</td>
     *                     </tr>
     *                     </tbody>
     *                     </table>
     */
    public function ttfText(
        float $size,
        float $angle,
        int $x,
        int $y,
        int $color,
        string $fontFile,
        string $text
    ): array|false {
        return \imagettftext(
            $this->image,
            $size,
            $angle,
            $x,
            $y,
            $color,
            $fontFile,
            $text
        );
    }
}
