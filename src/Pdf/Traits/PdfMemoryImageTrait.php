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

use App\Pdf\PdfVariableStreamWrapper;
use fpdf\PdfDocument;
use fpdf\PdfException;

/**
 * Trait to output memory images.
 *
 * @psalm-require-extends PdfDocument
 */
trait PdfMemoryImageTrait
{
    private const MEMORY_PROTOCOL = 'memory';

    /**
     * Output a GD image.
     *
     * This function convert first the given image to the portable network graphics format ('png') before output.
     *
     * @param \GdImage   $image  the GD image to output
     * @param ?float     $x      the abscissa of the upper-left corner. If <code>null</code>, the current abscissa is
     *                           used.
     * @param ?float     $y      the ordinate of the upper-left corner. If <code>null</code>, the current
     *                           ordinate is used; moreover, a page break is triggered first if necessary (in case
     *                           automatic page breaking is enabled) and, after the call, the current ordinate
     *                           is move to the bottom of the image.
     * @param float      $width  the width of the image in the page. There are three cases:
     *                           <ul>
     *                           <li>If the value is positive, it represents the width in user unit.</li>
     *                           <li>If the value is negative, the absolute value represents the horizontal resolution
     *                           in dpi.</li>
     *                           <li>If the value is not specified or equal to zero, it is automatically
     *                           calculated.</li>
     *                           </ul>
     * @param float      $height the height of the image in the page. There are three cases:
     *                           <ul>
     *                           <li>If the value is positive, it represents the width in user unit.</li>
     *                           <li>If the value is negative, the absolute value represents the horizontal resolution
     *                           in dpi.</li>
     *                           <li>If the value is not specified or equal to zero, it is automatically
     *                           calculated.</li>
     *                           </ul>
     * @param string|int $link   the URL or an identifier returned by <code>addLink()</code>
     *
     * @throws PdfException if the image can not be converted or if the stream wrapper can not be registered
     */
    public function imageGD(
        \GdImage $image,
        ?float $x = null,
        ?float $y = null,
        float $width = 0.0,
        float $height = 0.0,
        string|int $link = ''
    ): void {
        \ob_start();
        $result = \imagepng($image);
        $data = \ob_get_clean();
        if (!$result || !\is_string($data)) {
            throw new PdfException('Unable to convert the GD image to portable network graphics format.');
        }
        $this->imageMemory($data, $x, $y, $width, $height, $link);
    }

    /**
     * Output an image data.
     *
     * @param string     $data   the image data to output
     * @param ?float     $x      the abscissa of the upper-left corner. If <code>null</code>, the current abscissa is
     *                           used.
     * @param ?float     $y      the ordinate of the upper-left corner. If <code>null</code>, the current
     *                           ordinate is used; moreover, a page break is triggered first if necessary (in case
     *                           automatic page breaking is enabled) and, after the call, the current ordinate
     *                           is move to the bottom of the image.
     * @param float      $width  the width of the image in the page. There are three cases:
     *                           <ul>
     *                           <li>If the value is positive, it represents the width in user unit.</li>
     *                           <li>If the value is negative, the absolute value represents the horizontal resolution
     *                           in dpi.</li>
     *                           <li>If the value is not specified or equal to zero, it is automatically
     *                           calculated.</li>
     *                           </ul>
     * @param float      $height the height of the image in the page. There are three cases:
     *                           <ul>
     *                           <li>If the value is positive, it represents the width in user unit.</li>
     *                           <li>If the value is negative, the absolute value represents the horizontal resolution
     *                           in dpi.</li>
     *                           <li>If the value is not specified or equal to zero, it is automatically
     *                           calculated.</li>
     *                           </ul>
     * @param string|int $link   the URL or an identifier returned by <code>addLink()</code>
     *
     * @throws PdfException if the image data is invalid or if the stream wrapper can not be registered
     */
    public function imageMemory(
        string $data,
        ?float $x = null,
        ?float $y = null,
        float $width = 0.0,
        float $height = 0.0,
        string|int $link = ''
    ): void {
        $this->registerWrapper();
        $variable = 'image-' . \md5($data);
        $filename = \sprintf('%s://%s', self::MEMORY_PROTOCOL, $variable);
        $GLOBALS[$variable] = $data;

        try {
            $size = \getimagesize($filename);
            if (!\is_array($size)) {
                throw new PdfException(\sprintf('Incorrect image data: "%s".', $filename));
            }
            $mime = $size['mime'];
            if ('' === $mime || !\str_contains($mime, '/')) {
                throw new PdfException(\sprintf('Empty or incorrect mime type: "%s".', $mime));
            }
            $type = \substr((string) \strstr($mime, '/'), 1);
            $this->image($filename, $x, $y, $width, $height, $type, $link);
        } finally {
            unset($GLOBALS[$variable]);
        }
    }

    /**
     * Register the memory variable wrapper.
     */
    private function registerWrapper(): void
    {
        if (\in_array(self::MEMORY_PROTOCOL, \stream_get_wrappers(), true)) {
            return;
        }
        if (\stream_wrapper_register(self::MEMORY_PROTOCOL, PdfVariableStreamWrapper::class)) {
            return;
        }

        throw new PdfException(\sprintf('Unable to register the stream wrapper protocol "%s://".', self::MEMORY_PROTOCOL));
    }
}
