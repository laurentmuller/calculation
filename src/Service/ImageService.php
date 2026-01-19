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
     * The allocated colors.
     *
     * @var array<string, int>
     */
    private array $colors = [];

    private function __construct(private readonly \GdImage $image, private readonly ?string $filename = null)
    {
    }

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
     * @param int<0, 255> $red   the value of the red component
     * @param int<0, 255> $green the value of the green component
     * @param int<0, 255> $blue  the value of the blue component
     *
     * @return int the color identifier
     */
    public function allocate(int $red, int $green, int $blue): int
    {
        $key = \sprintf('%d-%d-%d', $red, $green, $blue);

        return $this->colors[$key] ??= (int) \imagecolorallocate($this->image, $red, $green, $blue);
    }

    /**
     * Allocate the black color (red: 0, green: 0, blue: 0) for this image.
     *
     * @return int the color identifier
     */
    public function allocateBlack(): int
    {
        return $this->allocate(0, 0, 0);
    }

    /**
     * Allocate the white color (red: 255, green: 255, blue: 255) for this image.
     *
     * @return int the color identifier
     */
    public function allocateWhite(): int
    {
        return $this->allocate(255, 255, 255);
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
        return \imagefilledrectangle(
            image: $this->image,
            x1: $x,
            y1: $y,
            x2: $x + $width,
            y2: $y + $height,
            color: $color
        );
    }

    /**
     * Create a new image handler from a file or a URL.
     *
     * This method uses the file extension to create the handler.
     *
     * @param string $filename the path to the image
     *
     * @throws \InvalidArgumentException if the file extension is not supported or if the image cannot be loaded
     */
    public static function fromFile(string $filename): self
    {
        $fileExtension = FileUtils::getExtension($filename, true);
        $imageExtension = ImageExtension::tryFrom($fileExtension);
        if (!$imageExtension instanceof ImageExtension) {
            throw new \InvalidArgumentException(\sprintf('Unsupported file image extension "%s".', $filename));
        }

        $image = $imageExtension->createImage($filename);
        if (!$image instanceof \GdImage) {
            throw new \InvalidArgumentException(\sprintf('Unable to load image from "%s".', $filename));
        }

        return new self($image, $filename);
    }

    /**
     * Create a new true color image handler.
     *
     * @param positive-int $width  the image width
     * @param positive-int $height the image height
     */
    public static function fromTrueColor(int $width, int $height): self
    {
        /** @var \GdImage $image */
        $image = \imagecreatetruecolor($width, $height);

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
        return \imagerectangle(
            image: $this->image,
            x1: $x,
            y1: $y,
            x2: $x + $width,
            y2: $y + $height,
            color: $color
        );
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
        return \imagettfbbox(
            size: $size,
            angle: $angle,
            font_filename: $fontFile,
            string: $text
        );
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
            image: $this->image,
            size: $size,
            angle: $angle,
            x: $x,
            y: $y,
            color: $color,
            font_filename: $fontFile,
            text: $text
        );
    }
}
