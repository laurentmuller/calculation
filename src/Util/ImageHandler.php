<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Util;

use App\Interfaces\ImageExtensionInterface;

/**
 * Handler for images.
 *
 * The underlaying image resource and allocated colors are automatically destroyed as soon
 * as there are no other references to this instance.
 *
 * @author Laurent Muller
 */
class ImageHandler implements ImageExtensionInterface
{
    /**
     * The allocate colors.
     *
     * @var int[]
     */
    protected $colors = [];

    /**
     * The image file name.
     *
     * @var string
     */
    protected $filename;

    /**
     * The image to handle.
     *
     * @var resource
     */
    protected $image;

    /**
     * Constructor.
     *
     * @param resource $image    the image to handle
     * @param string   $filename the file name or null if none
     *
     * @throws \InvalidArgumentException if the given image is not a resource
     */
    public function __construct($image, ?string $filename = null)
    {
        if (!\is_resource($image)) {
            throw new \InvalidArgumentException('The image must be resource.');
        }

        $this->image = $image;
        $this->filename = $filename;
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
     * The color is automatically de-allocate as soon as there are no other references to this instance.
     *
     * @param int $red   the value of red component
     * @param int $green the value of green component
     * @param int $blue  the value of blue component
     *
     * @return int|bool the color identifier on success, false if the allocation failed
     */
    public function allocate(int $red, int $green, int $blue)
    {
        if (false !== $color = \imagecolorallocate($this->image, $red, $green, $blue)) {
            $this->colors[] = $color;
        }

        return $color;
    }

    /**
     * Allocate a color for this image.
     *
     * The color is automatically de-allocate as soon as there are no other references to this instance.
     *
     * @param int $red   the value of red component
     * @param int $green the value of green component
     * @param int $blue  the value of blue component
     * @param int $alpha a value between 0 and 127
     *
     * @return int|bool the color identifier on success, false if the allocation failed
     */
    public function allocateAlpha(int $red = 0, int $green = 0, int $blue = 0, int $alpha = 127)
    {
        if (false !== $color = \imagecolorallocatealpha($this->image, $red, $green, $blue, $alpha)) {
            $this->colors[] = $color;
        }

        return $color;
    }

    /**
     * Allocate the black color for this image.
     *
     * @return int|bool the color identifier on success, false if the allocation failed
     */
    public function allocateBlack()
    {
        return $this->allocate(0, 0, 0);
    }

    /**
     * Allocate the white color for this image.
     *
     * @return int|bool the color identifier on success, false if the allocation failed
     */
    public function allocateWhite()
    {
        return $this->allocate(255, 255, 255);
    }

    /**
     * Set the blending mode for this image.
     *
     * @param bool $blendmode whether to enable the blending mode or not. On true color imagesthe default value is true otherwise.
     *
     * @return bool true on success or false on failure
     */
    public function alphaBlending(bool $blendmode): bool
    {
        return \imagealphablending($this->image, $blendmode);
    }

    /**
     * Copy and resize part of an image with resampling.
     *
     * @param ImageHandler $dst_image the destination image handler
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
    public function copyResampled(self $dst_image, int $dst_x, int $dst_y, int $src_x, int $src_y, int $dst_w, int $dst_h, int $src_w, int $src_h): bool
    {
        return \imagecopyresampled($dst_image->image, $this->image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
    }

    /**
     * Fill this image's bounds with the given color.
     *
     * @param int $color the fill color. A color identifier created with allocate.
     *
     * @return bool true on success or false on failure
     *
     * @see ImageHandler::allocate()
     */
    public function fill(int $color): bool
    {
        return \imagefill($this->image, 0, 0, $color);
    }

    /**
     * Create a new image handler from file or URL.
     *
     * @param string $filename the path to the PNG image
     *
     * @return ImageHandler|null an image handler on success, <code>null</code> on errors
     */
    public static function fromBmp(string $filename)
    {
        if (false !== $image = \imagecreatefrombmp($filename)) {
            return new self($image, $filename);
        }

        return null;
    }

    /**
     * Create a new image handler from file or URL.
     *
     * @param string $filename the path to the PNG image
     *
     * @return ImageHandler|null an image handler on success, <code>null</code> on errors
     */
    public static function fromGif(string $filename)
    {
        if (false !== $image = \imagecreatefromgif($filename)) {
            return new self($image, $filename);
        }

        return null;
    }

    /**
     * Create a new image handler from file or URL.
     *
     * @param string $filename the path to the PNG image
     *
     * @return ImageHandler|null an image handler on success, <code>null</code> on errors
     */
    public static function fromJpeg(string $filename)
    {
        if (false !== $image = \imagecreatefromjpeg($filename)) {
            return new self($image, $filename);
        }

        return null;
    }

    /**
     * Create a new image handler from file or URL. This methos uses the file extension to create the handler.
     *
     * @param string $filename the path to the image
     *
     * @return ImageHandler|null an image handler on success, <code>null</code> on errors
     */
    public static function fromName(string $filename)
    {
        $ext = \strtolower(\pathinfo($filename, \PATHINFO_EXTENSION));
        switch ($ext) {
            case self::EXTENSION_BMP:
                return self::fromBmp($filename);

            case self::EXTENSION_GIF:
                return self::fromGif($filename);

            case self::EXTENSION_JPEG:
            case self::EXTENSION_JPG:
                return self::fromJpeg($filename);

            case self::EXTENSION_PNG:
                return self::fromPng($filename);

            case self::EXTENSION_XBM:
                return self::fromXbm($filename);

            default:
                return null;
        }
    }

    /**
     * Create a new image handler from file or URL.
     *
     * @param string $filename the path to the PNG image
     *
     * @return ImageHandler|null an image handler on success, <code>null</code> on errors
     */
    public static function fromPng(string $filename)
    {
        if (false !== $image = \imagecreatefrompng($filename)) {
            return new self($image, $filename);
        }

        return null;
    }

    /**
     * Create a new true color image handler.
     *
     * @param int $width  the image width
     * @param int $height the image height
     *
     * @return ImageHandler|null an image handler on success, <code>null</code> on errors
     */
    public static function fromTrueColor(int $width, int $height)
    {
        if (false !== $image = \imagecreatetruecolor($width, $height)) {
            return new self($image);
        }

        return null;
    }

    /**
     * Create a new image handler from file or URL.
     *
     * @param string $filename the path to the WBMP (Wireless Bitmaps) image
     *
     * @return ImageHandler|null an image handler on success, <code>null</code> on errors
     */
    public static function fromWbmp(string $filename)
    {
        if (false !== $image = \imagecreatefromwbmp($filename)) {
            return new self($image, $filename);
        }

        return null;
    }

    /**
     * Create a new image handler from file or URL.
     *
     * @param string $filename the path to the WebP image
     *
     * @return ImageHandler|null an image handler on success, <code>null</code> on errors
     */
    public static function fromWebp(string $filename)
    {
        if (false !== $image = \imagecreatefromwebp($filename)) {
            return new self($image, $filename);
        }

        return null;
    }

    /**
     * Create a new image handler from file or URL.
     *
     * @param string $filename the path to the PNG image
     *
     * @return ImageHandler|null an image handler on success, <code>null</code> on errors
     */
    public static function fromXbm(string $filename)
    {
        if (false !== $image = \imagecreatefromxbm($filename)) {
            return new self($image, $filename);
        }

        return null;
    }

    /**
     * Create a new image handler from file or URL.
     *
     * @param string $filename the path to the XPM image
     *
     * @return ImageHandler|null an image handler on success, <code>null</code> on errors
     */
    public static function fromXpm(string $filename)
    {
        if (false !== $image = \imagecreatefromxpm($filename)) {
            return new self($image, $filename);
        }

        return null;
    }

    /**
     * Gets the loaded file name.
     *
     * @return string|null the file name
     */
    public function getFilename(): ?string
    {
        return $this->filename;
    }

    /**
     * Gets the image.
     *
     * @return resource
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Draw a line.
     *
     * @param int $x1    the x-coordinate for first point
     * @param int $y1    the y-coordinate for first point
     * @param int $x2    the x-coordinate for second point
     * @param int $y2    the y-coordinate for second point
     * @param int $color the line color. A color identifier created with allocate.
     *
     * @return bool true on success or false on failure
     *
     * @see ImageHandler::allocate()
     */
    public function line(int $x1, int $y1, int $x2, int $y2, int $color): bool
    {
        return \imageline($this->image, $x1, $y1, $x2, $y2, $color);
    }

    /**
     * Get the horizontal resolution of this image in dot per inch (DPI).
     *
     * @param int $default the default resolution to use on failure
     *
     * @return int the resolution
     *
     * @see ImageExtensionInterface::DEFAULT_RESOLUTION
     */
    public function resolution(int $default = self::DEFAULT_RESOLUTION): int
    {
        if (\function_exists('imageresolution')) {
            if ($resolutions = \imageresolution($this->image)) {
                return $resolutions[0];
            }
        }

        return $default;
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
     * @param int $color a color identifier created with allocate
     *
     * @return bool true on success or false on failure
     *
     * @see ImageHandler::allocate()
     */
    public function setPixel(int $x, int $y, int $color): bool
    {
        return \imagesetpixel($this->image, $x, $y, $color);
    }

    /**
     * Output a BMP image to either the browser or a file.
     *
     * @param mixed $to the path or an open stream resource (which is automatically being closed
     *                  after this function returns) to save the file to. If not set or null, the
     *                  raw image stream will be outputted directly.</p>
     *                  <p>
     *                  <code>null</code> is invalid if the quality and filters arguments are not used.
     *                  </p>
     *
     * @return bool true on success or false on failure
     */
    public function toBmp($to = null): bool
    {
        return \imagebmp($this->image, $to);
    }

    /**
     * Output a GIF image to either the browser or a file.
     *
     * @param mixed $to the path or an open stream resource (which is automatically being closed
     *                  after this function returns) to save the file to. If not set or null, the
     *                  raw image stream will be outputted directly.</p>
     *                  <p>
     *                  <code>null</code> is invalid if the quality and filters arguments are not used.
     *                  </p>
     *
     * @return bool true on success or false on failure
     */
    public function toGif($to = null): bool
    {
        return \imagegif($this->image, $to);
    }

    /**
     * Output a JPEG image to either the browser or a file.
     *
     * @param mixed $to      the path or an open stream resource (which is automatically being closed
     *                       after this function returns) to save the file to. If not set or null, the
     *                       raw image stream will be outputted directly.</p>
     *                       <p>
     *                       <code>null</code> is invalid if the quality and filters arguments are not used.
     *                       </p>
     * @param int   $quality the quality is optional, and ranges from 0 (worst quality, smaller file)
     *                       to 100 (best quality, biggest file). The default is the default IJG quality value
     *                       (about 75).
     *
     * @return bool true on success or false on failure
     */
    public function toJpeg($to = null, int $quality = -1): bool
    {
        return \imagejpeg($this->image, $to, $quality);
    }

    /**
     * Output a PNG image to either the browser or a file.
     *
     * @param mixed $to      the path or an open stream resource (which is automatically being closed
     *                       after this function returns) to save the file to. If not set or null, the
     *                       raw image stream will be outputted directly.</p>
     *                       <p>
     *                       <code>null</code> is invalid if the quality and filters arguments are not used.
     *                       </p>
     * @param int   $quality the compression level: from 0 (no compression) to 9. The current default is 6.
     * @param int   $filters allows reducing the PNG file size. It is a bitmask field which may be set to any
     *                       combination of the PNG_FILTER_XX constants. PNG_NO_FILTER or PNG_ALL_FILTERS may also be
     *                       used to respectively disable or activate all filters.
     *
     * @return bool true on success or false on failure
     */
    public function toPng($to = null, int $quality = -1, int $filters = -1): bool
    {
        return \imagepng($this->image, $to, $quality, $filters);
    }

    /**
     * Output a WBMP (Wireless Bitmaps) image to either the browser or a file.
     *
     * @param mixed $to         the path or an open stream resource (which is automatically being closed
     *                          after this function returns) to save the file to. If not set or null, the
     *                          raw image stream will be outputted directly.</p>
     *                          <p>
     *                          <code>null</code> is invalid if the quality and filters arguments are not used.
     *                          </p>
     * @param int   $foreground you can set the foreground color with this parameter by setting an
     *                          identifier obtained from allocate. The default foreground color
     *                          is black. All other colors are treated as background.
     *
     * @return bool true on success or false on failure
     */
    public function toWbmp($to = null, ?int $foreground = null): bool
    {
        if ($foreground) {
            return \imagewbmp($this->image, $to, $foreground);
        }

        return \imagewbmp($this->image, $to);
    }

    /**
     * Output a JPEG image to either the browser or a file.
     *
     * @param mixed $to      the path or an open stream resource (which is automatically being closed
     *                       after this function returns) to save the file to. If not set or null, the
     *                       raw image stream will be outputted directly.</p>
     *                       <p>
     *                       <code>null</code> is invalid if the quality and filters arguments are not used.
     *                       </p>
     * @param int   $quality the ranges from 0 (worst quality, smaller file) to 100 (best quality, biggest file)
     *
     * @return bool true on success or false on failure
     */
    public function toWebp($to = null, int $quality = 80): bool
    {
        return \imagewebp($this->image, $to, $quality);
    }

    /**
     * Output a XBM image to either the browser or a file.
     *
     * @param mixed $to         the path or an open stream resource (which is automatically being closed
     *                          after this function returns) to save the file to. If not set or null, the
     *                          raw image stream will be outputted directly.</p>
     *                          <p>
     *                          <code>null</code> is invalid if the quality and filters arguments are not used.
     *                          </p>
     * @param int   $foreground you can set the foreground color with this parameter by setting an
     *                          identifier obtained from allocate. The default foreground color
     *                          is black. All other colors are treated as background.
     *
     * @return bool true on success or false on failure
     */
    public function toXbm($to = null, ?int $foreground = null): bool
    {
        if ($foreground) {
            return \imagexbm($this->image, $to, $foreground);
        }

        return \imagexbm($this->image, $to);
    }

    /**
     * Define a color as transparent.
     *
     * @param int $color a color identifier created with allocate
     *
     * @return int the identifier of the new transparent color
     */
    public function transparent(int $color): int
    {
        return \imagecolortransparent($this->image, $color);
    }

    /**
     * Gets the bounding box of a text using TrueType fonts.
     *
     * @param float  $size     the font size
     * @param float  $angle    the angle in degrees in which text will be measured
     * @param string $fontfile the path to the TrueType font
     * @param string $text     the string to be measured
     *
     * @return array|bool an array with 8 elements representing four points making the bounding box of the
     *                    text on success and false on error.<br>
     *                    The points are relative to the text regardless of the angle, so "upper left" means in the top left-hand
     *                    corner seeing the text horizontally.<br><br>
     *                    <table class="table table-bordered" border="1" cellpadding="5" style="border-collapse: collapse;">
     *                    <tr>
     *                    <th>Key</th>
     *                    <th>Content</th>
     *                    </tr>
     *                    <tr>
     *                    <td>0</td>
     *                    <td>The lower left corner, X position.</td>
     *                    </tr>
     *                    <tr>
     *                    <td>1</td>
     *                    <td>The lower left corner, Y position.</td>
     *                    </tr>
     *                    <tr>
     *                    <td>2</td>
     *                    <td>The lower right corner, X position.</td>
     *                    </tr>
     *                    <tr>
     *                    <td>3</td>
     *                    <td>The lower right corner, Y position.</td>
     *                    </tr>
     *                    <tr>
     *                    <td>4</td>
     *                    <td>The upper right corner, X position.</td>
     *                    </tr>
     *                    <tr>
     *                    <td>5</td>
     *                    <td>The upper right corner, Y position.</td>
     *                    </tr>
     *                    <tr>
     *                    <td>6</td>
     *                    <td>The upper left corner, X position.</td>
     *                    </tr>
     *                    <tr>
     *                    <td>7</td>
     *                    <td>The upper left corner, Y position.</td>
     *                    </tr>
     *                    </table>
     */
    public function ttfBox(float $size, float $angle, string $fontfile, string $text)
    {
        return \imagettfbbox($size, $angle, $fontfile, $text);
    }

    /**
     * Gets the height of a text using TrueType fonts.
     *
     * @param float  $size     the font size
     * @param float  $angle    the angle in degrees in which text will be measured
     * @param string $fontfile the path to the TrueType font
     * @param string $text     the string to be measured
     *
     * @return int the text height or 0 on error
     *
     * @see ImageHandler::ttfBox()
     */
    public function ttfHeight(float $size, float $angle, string $fontfile, string $text): int
    {
        return $this->ttfSize($size, $angle, $fontfile, $text)[1];
    }

    /**
     * Gets the width and the height of a text using TrueType fonts.
     *
     * @param float  $size     the font size
     * @param float  $angle    the angle in degrees in which text will be measured
     * @param string $fontfile the path to the TrueType font
     * @param string $text     the string to be measured
     *
     * @return array an array with the text width and the text height or an empty array ([0, 0]) on error
     *
     * @see ImageHandler::ttfBox()
     */
    public function ttfSize(float $size, float $angle, string $fontfile, string $text): array
    {
        if (false !== $box = $this->ttfBox($size, $angle, $fontfile, $text)) {
            $values = [$box[0], $box[2], $box[4], $box[6]];
            $width = \max($values) - \min($values);

            $values = [$box[1], $box[3], $box[5], $box[7]];
            $height = \max($values) - \min($values);

            return [$width, $height];
        }

        return [0, 0];
    }

    /**
     * Write text to this image using TrueType fonts.
     *
     * @param float  $size     the font size
     * @param float  $angle    The angle in degrees, with 0 degrees being left-to-right reading text.
     *                         Higher values represent a counter-clockwise rotation. For example, a
     *                         value of 90 would result in bottom-to-top reading text.
     * @param int    $x        The coordinates given by x and y will define the basepoint of the first
     *                         character (roughly the lower-left corner of the character)
     * @param int    $y        The y-coordinate. This sets the position of the fonts baseline, not the
     *                         very bottom of the character.
     * @param int    $color    a color identifier created with allocate
     * @param string $fontfile the path to the TrueType font
     * @param string $text     The text string in UTF-8 encoding.
     *                         <br>
     *                         May include decimal numeric character references (of the form:
     *                         &amp;#8364;) to access characters in a font beyond position 127.
     *                         The hexadecimal format (like &amp;#xA9;) is supported.
     *                         Strings in UTF-8 encoding can be passed directly.
     *                         <br>
     *                         Named entities, such as &amp;copy;, are not supported. Consider using
     *                         html_entity_decode
     *                         to decode these named entities into UTF-8 strings.
     *                         <br>
     *                         If a character is used in the string which is not supported by the
     *                         font, a hollow rectangle will replace the character.
     *
     * @return array|bool an array with 8 elements representing four points making the bounding box of the
     *                    text on success and false on error.<br>
     *                    The points are relative to the text regardless of the angle, so "upper left" means in the top left-hand
     *                    corner seeing the text horizontally.<br>
     *                    <table class="table table-bordered" border="1" cellpadding="5" style="border-collapse: collapse;">
     *                    <tr>
     *                    <th>Key</th>
     *                    <th>Content</th>
     *                    </tr>
     *                    <tr>
     *                    <td>0</td>
     *                    <td>The lower left corner, X position.</td>
     *                    </tr>
     *                    <tr>
     *                    <td>1</td>
     *                    <td>The lower left corner, Y position.</td>
     *                    </tr>
     *                    <tr>
     *                    <td>2</td>
     *                    <td>The lower right corner, X position.</td>
     *                    </tr>
     *                    <tr>
     *                    <td>3</td>
     *                    <td>The lower right corner, Y position.</td>
     *                    </tr>
     *                    <tr>
     *                    <td>4</td>
     *                    <td>The upper right corner, X position.</td>
     *                    </tr>
     *                    <tr>
     *                    <td>5</td>
     *                    <td>The upper right corner, Y position.</td>
     *                    </tr>
     *                    <tr>
     *                    <td>6</td>
     *                    <td>The upper left corner, X position.</td>
     *                    </tr>
     *                    <tr>
     *                    <td>7</td>
     *                    <td>The upper left corner, Y position.</td>
     *                    </tr>
     *                    </table>
     */
    public function ttfText(float $size, float $angle, int $x, int $y, int $color, string $fontfile, string $text)
    {
        return \imagettftext($this->image, $size, $angle, $x, $y, $color, $fontfile, $text);
    }

    /**
     * Gets the width of a text using TrueType fonts.
     *
     * @param float  $size     the font size
     * @param float  $angle    the angle in degrees in which text will be measured
     * @param string $fontfile the path to the TrueType font
     * @param string $text     the string to be measured
     *
     * @return int the text width or 0 on error
     *
     * @see ImageHandler::ttfBox()
     */
    public function ttfWidth(float $size, float $angle, string $fontfile, string $text): int
    {
        return $this->ttfSize($size, $angle, $fontfile, $text)[0];
    }
}
