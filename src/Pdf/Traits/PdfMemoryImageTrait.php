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

namespace App\Pdf\Traits;

use App\Model\ImageData;
use fpdf\PdfDocument;
use fpdf\PdfException;

/**
 * Trait to output memory images.
 *
 * @psalm-require-extends PdfDocument
 */
trait PdfMemoryImageTrait
{
    /**
     * Output an AVIF raster image from a file or a URL.
     *
     * @param string          $file   the image path
     * @param ?float          $x      the abscissa of the upper-left corner. If <code>null</code>, the current abscissa
     *                                is used.
     * @param ?float          $y      the ordinate of the upper-left corner. If <code>null</code>, the current ordinate
     *                                is used; moreover, a page break is triggered first if necessary (in case
     *                                automatic page breaking is enabled) and, after the call, the current ordinate
     *                                is moved to the bottom of the image.
     * @param float           $width  the width of the image in the page. There are three cases:
     *                                <ul>
     *                                <li>If the value is positive, it represents the width in user unit.</li>
     *                                <li>If the value is negative, the absolute value represents the horizontal
     *                                resolution in dpi.</li>
     *                                <li>If the value is not specified or equal to zero, it is automatically
     *                                calculated.</li>
     *                                </ul>
     * @param float           $height the height of the image in the page. There are three cases:
     *                                <ul>
     *                                <li>If the value is positive, it represents the width in user unit.</li>
     *                                <li>If the value is negative, the absolute value represents the horizontal
     *                                resolution in dpi.</li>
     *                                <li>If the value is not specified or equal to zero, it is automatically
     *                                calculated.</li>
     *                                </ul>
     * @param string|int|null $link   the URL or an identifier returned by the <code>addLink()</code> function
     *
     * @throws PdfException if the image file does not exist, or if the image cannot be converted
     */
    public function imageAvif(
        string $file,
        ?float $x = null,
        ?float $y = null,
        float $width = 0.0,
        float $height = 0.0,
        string|int|null $link = null
    ): void {
        $this->imageFromLoader(
            static fn (): \GdImage|false => \imagecreatefromavif($file),
            $file,
            $x,
            $y,
            $width,
            $height,
            $link
        );
    }

    /**
     * Output a Bitmap image from a file or a URL.
     *
     * @param string          $file   the image path
     * @param ?float          $x      the abscissa of the upper-left corner. If <code>null</code>, the current abscissa
     *                                is used.
     * @param ?float          $y      the ordinate of the upper-left corner. If <code>null</code>, the current ordinate
     *                                is used; moreover, a page break is triggered first if necessary (in case
     *                                automatic page breaking is enabled) and, after the call, the current ordinate
     *                                is moved to the bottom of the image.
     * @param float           $width  the width of the image in the page. There are three cases:
     *                                <ul>
     *                                <li>If the value is positive, it represents the width in user unit.</li>
     *                                <li>If the value is negative, the absolute value represents the horizontal
     *                                resolution in dpi.</li>
     *                                <li>If the value is not specified or equal to zero, it is automatically
     *                                calculated.</li>
     *                                </ul>
     * @param float           $height the height of the image in the page. There are three cases:
     *                                <ul>
     *                                <li>If the value is positive, it represents the width in user unit.</li>
     *                                <li>If the value is negative, the absolute value represents the horizontal
     *                                resolution in dpi.</li>
     *                                <li>If the value is not specified or equal to zero, it is automatically
     *                                calculated.</li>
     *                                </ul>
     * @param string|int|null $link   the URL or an identifier returned by the <code>addLink()</code> function
     *
     * @throws PdfException if the image file does not exist, or if the image cannot be converted
     */
    public function imageBmp(
        string $file,
        ?float $x = null,
        ?float $y = null,
        float $width = 0.0,
        float $height = 0.0,
        string|int|null $link = null
    ): void {
        $this->imageFromLoader(
            static fn (): \GdImage|false => \imagecreatefrombmp($file),
            $file,
            $x,
            $y,
            $width,
            $height,
            $link
        );
    }

    /**
     * Output the given image data.
     *
     * @param ImageData|string $data   the image data or the image content to output
     * @param ?float           $x      the abscissa of the upper-left corner. If <code>null</code>, the current abscissa
     *                                 is used.
     * @param ?float           $y      the ordinate of the upper-left corner. If <code>null</code>, the current ordinate
     *                                 is used; moreover, a page break is triggered first if necessary (in case
     *                                 automatic page breaking is enabled) and, after the call, the current ordinate is
     *                                 moved to the bottom of the image.
     * @param float            $width  the width of the image in the page. There are three cases:
     *                                 <ul>
     *                                 <li>If the value is positive, it represents the width in user unit.</li>
     *                                 <li>If the value is negative, the absolute value represents the horizontal
     *                                 resolution in dpi.</li>
     *                                 <li>If the value is not specified or equal to zero, it is automatically
     *                                 calculated.</li>
     *                                 </ul>
     * @param float            $height the height of the image in the page. There are three cases:
     *                                 <ul>
     *                                 <li>If the value is positive, it represents the width in user unit.</li>
     *                                 <li>If the value is negative, the absolute value represents the horizontal
     *                                 resolution in dpi.</li>
     *                                 <li>If the value is not specified or equal to zero, it is automatically
     *                                 calculated.</li>
     *                                 </ul>
     * @param string|int|null  $link   the URL or an identifier returned by the <code>addLink()</code> function
     *
     * @throws PdfException if the image cannot be processed
     */
    public function imageData(
        ImageData|string $data,
        ?float $x = null,
        ?float $y = null,
        float $width = 0.0,
        float $height = 0.0,
        string|int|null $link = null
    ): void {
        if (\is_string($data)) {
            $data = new ImageData($data);
        }
        $this->image(
            $data->getFileName(),
            $x,
            $y,
            $width,
            $height,
            $data->getFileType(),
            $link
        );
    }

    /**
     * Output a GD image.
     *
     * This function converts first the given image to the portable network graphics format ('png') before output.
     * The image is destroyed after this call.
     *
     * @param \GdImage        $image  the GD image to output
     * @param ?float          $x      the abscissa of the upper-left corner. If <code>null</code>, the current abscissa
     *                                is used.
     * @param ?float          $y      the ordinate of the upper-left corner. If <code>null</code>, the current ordinate
     *                                is used; moreover, a page break is triggered first if necessary (in case
     *                                automatic page breaking is enabled) and, after the call, the current ordinate
     *                                is moved to the bottom of the image.
     * @param float           $width  the width of the image in the page. There are three cases:
     *                                <ul>
     *                                <li>If the value is positive, it represents the width in user unit.</li>
     *                                <li>If the value is negative, the absolute value represents the horizontal
     *                                resolution in dpi.</li>
     *                                <li>If the value is not specified or equal to zero, it is automatically
     *                                calculated.</li>
     *                                </ul>
     * @param float           $height the height of the image in the page. There are three cases:
     *                                <ul>
     *                                <li>If the value is positive, it represents the width in user unit.</li>
     *                                <li>If the value is negative, the absolute value represents the horizontal
     *                                resolution in dpi.</li>
     *                                <li>If the value is not specified or equal to zero, it is automatically
     *                                calculated.</li>
     *                                </ul>
     * @param string|int|null $link   the URL or an identifier returned by the <code>addLink()</code> function
     *
     * @throws PdfException if the image cannot be converted
     */
    public function imageGD(
        \GdImage $image,
        ?float $x = null,
        ?float $y = null,
        float $width = 0.0,
        float $height = 0.0,
        string|int|null $link = null
    ): void {
        \ob_start();
        $result = \imagepng($image);
        $data = \ob_get_clean();
        \imagedestroy($image);

        if (!$result || !\is_string($data) || '' === $data) {
            throw PdfException::instance('Unable to convert the GD image to portable network graphics format.');
        }

        $imageData = ImageData::instance(
            data: $data,
            mimeType: 'image/png'
        );
        $this->imageData($imageData, $x, $y, $width, $height, $link);
    }

    /**
     * Output a WBMP image from a file or a URL.
     *
     * @param string          $file   the image path
     * @param ?float          $x      the abscissa of the upper-left corner. If <code>null</code>, the current abscissa
     *                                is used.
     * @param ?float          $y      the ordinate of the upper-left corner. If <code>null</code>, the current ordinate
     *                                is used; moreover, a page break is triggered first if necessary (in case
     *                                automatic page breaking is enabled) and, after the call, the current ordinate
     *                                is moved to the bottom of the image.
     * @param float           $width  the width of the image in the page. There are three cases:
     *                                <ul>
     *                                <li>If the value is positive, it represents the width in user unit.</li>
     *                                <li>If the value is negative, the absolute value represents the horizontal
     *                                resolution in dpi.</li>
     *                                <li>If the value is not specified or equal to zero, it is automatically
     *                                calculated.</li>
     *                                </ul>
     * @param float           $height the height of the image in the page. There are three cases:
     *                                <ul>
     *                                <li>If the value is positive, it represents the width in user unit.</li>
     *                                <li>If the value is negative, the absolute value represents the horizontal
     *                                resolution in dpi.</li>
     *                                <li>If the value is not specified or equal to zero, it is automatically
     *                                calculated.</li>
     *                                </ul>
     * @param string|int|null $link   the URL or an identifier returned by the <code>addLink()</code> function
     *
     * @throws PdfException if the image file does not exist, or if the image cannot be converted
     */
    public function imageWbmp(
        string $file,
        ?float $x = null,
        ?float $y = null,
        float $width = 0.0,
        float $height = 0.0,
        string|int|null $link = null
    ): void {
        $this->imageFromLoader(
            static fn (): \GdImage|false => \imagecreatefromwbmp($file),
            $file,
            $x,
            $y,
            $width,
            $height,
            $link
        );
    }

    /**
     * Output a Webp image from a file or a URL.
     *
     * @param string          $file   the image path
     * @param ?float          $x      the abscissa of the upper-left corner. If <code>null</code>, the current abscissa
     *                                is used.
     * @param ?float          $y      the ordinate of the upper-left corner. If <code>null</code>, the current ordinate
     *                                is used; moreover, a page break is triggered first if necessary (in case
     *                                automatic page breaking is enabled) and, after the call, the current ordinate
     *                                is moved to the bottom of the image.
     * @param float           $width  the width of the image in the page. There are three cases:
     *                                <ul>
     *                                <li>If the value is positive, it represents the width in user unit.</li>
     *                                <li>If the value is negative, the absolute value represents the horizontal
     *                                resolution in dpi.</li>
     *                                <li>If the value is not specified or equal to zero, it is automatically
     *                                calculated.</li>
     *                                </ul>
     * @param float           $height the height of the image in the page. There are three cases:
     *                                <ul>
     *                                <li>If the value is positive, it represents the width in user unit.</li>
     *                                <li>If the value is negative, the absolute value represents the horizontal
     *                                resolution in dpi.</li>
     *                                <li>If the value is not specified or equal to zero, it is automatically
     *                                calculated.</li>
     *                                </ul>
     * @param string|int|null $link   the URL or an identifier returned by the <code>addLink()</code> function
     *
     * @throws PdfException if the image file does not exist, or if the image cannot be converted
     */
    public function imageWebp(
        string $file,
        ?float $x = null,
        ?float $y = null,
        float $width = 0.0,
        float $height = 0.0,
        string|int|null $link = null
    ): void {
        $this->imageFromLoader(
            static fn (): \GdImage|false => \imagecreatefromwebp($file),
            $file,
            $x,
            $y,
            $width,
            $height,
            $link
        );
    }

    /**
     * Output an XBM image from a file or a URL.
     *
     * @param string          $file   the image path
     * @param ?float          $x      the abscissa of the upper-left corner. If <code>null</code>, the current abscissa
     *                                is used.
     * @param ?float          $y      the ordinate of the upper-left corner. If <code>null</code>, the current ordinate
     *                                is used; moreover, a page break is triggered first if necessary (in case
     *                                automatic page breaking is enabled) and, after the call, the current ordinate
     *                                is moved to the bottom of the image.
     * @param float           $width  the width of the image in the page. There are three cases:
     *                                <ul>
     *                                <li>If the value is positive, it represents the width in user unit.</li>
     *                                <li>If the value is negative, the absolute value represents the horizontal
     *                                resolution in dpi.</li>
     *                                <li>If the value is not specified or equal to zero, it is automatically
     *                                calculated.</li>
     *                                </ul>
     * @param float           $height the height of the image in the page. There are three cases:
     *                                <ul>
     *                                <li>If the value is positive, it represents the width in user unit.</li>
     *                                <li>If the value is negative, the absolute value represents the horizontal
     *                                resolution in dpi.</li>
     *                                <li>If the value is not specified or equal to zero, it is automatically
     *                                calculated.</li>
     *                                </ul>
     * @param string|int|null $link   the URL or an identifier returned by the <code>addLink()</code> function
     *
     * @throws PdfException if the image file does not exist, or if the image cannot be converted
     */
    public function imageXbm(
        string $file,
        ?float $x = null,
        ?float $y = null,
        float $width = 0.0,
        float $height = 0.0,
        string|int|null $link = null
    ): void {
        $this->imageFromLoader(
            static fn (): \GdImage|false => \imagecreatefromxbm($file),
            $file,
            $x,
            $y,
            $width,
            $height,
            $link
        );
    }

    /**
     * Output XPM image from a file or a URL.
     *
     * @param string          $file   the image path
     * @param ?float          $x      the abscissa of the upper-left corner. If <code>null</code>, the current abscissa
     *                                is used.
     * @param ?float          $y      the ordinate of the upper-left corner. If <code>null</code>, the current ordinate
     *                                is used; moreover, a page break is triggered first if necessary (in case
     *                                automatic page breaking is enabled) and, after the call, the current ordinate
     *                                is moved to the bottom of the image.
     * @param float           $width  the width of the image in the page. There are three cases:
     *                                <ul>
     *                                <li>If the value is positive, it represents the width in user unit.</li>
     *                                <li>If the value is negative, the absolute value represents the horizontal
     *                                resolution in dpi.</li>
     *                                <li>If the value is not specified or equal to zero, it is automatically
     *                                calculated.</li>
     *                                </ul>
     * @param float           $height the height of the image in the page. There are three cases:
     *                                <ul>
     *                                <li>If the value is positive, it represents the width in user unit.</li>
     *                                <li>If the value is negative, the absolute value represents the horizontal
     *                                resolution in dpi.</li>
     *                                <li>If the value is not specified or equal to zero, it is automatically
     *                                calculated.</li>
     *                                </ul>
     * @param string|int|null $link   the URL or an identifier returned by the <code>addLink()</code> function
     *
     * @throws PdfException if the image file does not exist, or if the image cannot be converted
     */
    public function imageXpm(
        string $file,
        ?float $x = null,
        ?float $y = null,
        float $width = 0.0,
        float $height = 0.0,
        string|int|null $link = null
    ): void {
        $this->imageFromLoader(
            static fn (): \GdImage|false => \imagecreatefromxpm($file),
            $file,
            $x,
            $y,
            $width,
            $height,
            $link
        );
    }

    /**
     * Output an image from the given image loader.
     *
     * @param callable        $loader the callback to get a GD image
     * @param string          $file   the image path
     * @param ?float          $x      the abscissa of the upper-left corner. If <code>null</code>, the current abscissa
     *                                is used.
     * @param ?float          $y      the ordinate of the upper-left corner. If <code>null</code>, the current ordinate
     *                                is used; moreover, a page break is triggered first if necessary (in case
     *                                automatic page breaking is enabled) and, after the call, the current ordinate
     *                                is moved to the bottom of the image.
     * @param float           $width  the width of the image in the page. There are three cases:
     *                                <ul>
     *                                <li>If the value is positive, it represents the width in user unit.</li>
     *                                <li>If the value is negative, the absolute value represents the horizontal
     *                                resolution in dpi.</li>
     *                                <li>If the value is not specified or equal to zero, it is automatically
     *                                calculated.</li>
     *                                </ul>
     * @param float           $height the height of the image in the page. There are three cases:
     *                                <ul>
     *                                <li>If the value is positive, it represents the width in user unit.</li>
     *                                <li>If the value is negative, the absolute value represents the horizontal
     *                                resolution in dpi.</li>
     *                                <li>If the value is not specified or equal to zero, it is automatically
     *                                calculated.</li>
     *                                </ul>
     * @param string|int|null $link   the URL or an identifier returned by the <code>addLink()</code> function
     *
     * @throws PdfException if the image file does not exist, or if the image cannot be converted
     *
     * @psalm-param callable(string): (\GdImage|false) $loader
     */
    protected function imageFromLoader(
        callable $loader,
        string $file,
        ?float $x = null,
        ?float $y = null,
        float $width = 0.0,
        float $height = 0.0,
        string|int|null $link = null
    ): void {
        $image = $loader($file);
        if (!$image instanceof \GdImage) {
            throw PdfException::format('The image file "%s" is not a valid image.', $file);
        }
        $this->imageGD($image, $x, $y, $width, $height, $link);
    }
}
