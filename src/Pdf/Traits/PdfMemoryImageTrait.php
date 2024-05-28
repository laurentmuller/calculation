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
     * Output a GD image.
     *
     * This function converts first the given image to the portable network graphics format ('png') before output.
     *
     * @param \GdImage        $image  the GD image to output
     * @param ?float          $x      the abscissa of the upper-left corner. If <code>null</code>, the current abscissa
     *                                is used.
     * @param ?float          $y      the ordinate of the upper-left corner. If <code>null</code>, the current ordinate
     *                                is used; moreover, a page break is triggered first if necessary (in case
     *                                automatic page breaking is enabled) and, after the call, the current ordinate
     *                                is move to the bottom of the image.
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
     * @param string|int|null $link   the URL or an identifier returned by <code>addLink()</code>
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
        if (!$result || !\is_string($data)) {
            throw PdfException::instance('Unable to convert the GD image to portable network graphics format.');
        }
        $this->imageMemory($data, $x, $y, $width, $height, $link);
    }

    /**
     * Output image data.
     *
     * @param string          $data   the image data to output
     * @param ?float          $x      the abscissa of the upper-left corner. If <code>null</code>, the current abscissa
     *                                is used.
     * @param ?float          $y      the ordinate of the upper-left corner. If <code>null</code>, the current ordinate
     *                                is used; moreover, a page break is triggered first if necessary (in case automatic
     *                                page breaking is enabled) and, after the call, the current ordinate is move to
     *                                the bottom of the image.
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
     * @param string|int|null $link   the URL or an identifier returned by <code>addLink()</code>
     *
     * @throws PdfException if the image data is invalid
     */
    public function imageMemory(
        string $data,
        ?float $x = null,
        ?float $y = null,
        float $width = 0.0,
        float $height = 0.0,
        string|int|null $link = null
    ): void {
        $mimeType = $this->getImageMimeType($data);
        $fileType = $this->getImageFileType($mimeType);
        $filename = $this->getImageFileName($mimeType, $data);
        $this->image($filename, $x, $y, $width, $height, $fileType, $link);
    }

    private function getImageFileName(string $mimeType, string $data): string
    {
        return \sprintf('data://%s;base64,%s', $mimeType, \base64_encode($data));
    }

    private function getImageFileType(string $mimeType): string
    {
        return \substr((string) \strrchr($mimeType, '/'), 1);
    }

    private function getImageMimeType(string $data): string
    {
        $info = new \finfo(\FILEINFO_MIME_TYPE);
        $mime = $info->buffer($data);
        if (!\is_string($mime) || !\str_contains($mime, '/')) {
            throw PdfException::format('Empty or incorrect mime type: "%s".', (string) $mime);
        }

        return $mime;
    }
}
