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

namespace App\Pdf\Interfaces;

use fpdf\PdfDocument;
use fpdf\PdfException;

/**
 * @psalm-require-extends PdfDocument
 */
interface PdfMemoryImageInterface
{
    /**
     * Output the given image data.
     *
     * @param string          $data   the image data to output
     * @param ?float          $x      the abscissa of the upper-left corner. If <code>null</code>, the current abscissa
     *                                is used.
     * @param ?float          $y      the ordinate of the upper-left corner. If <code>null</code>, the current ordinate
     *                                is used; moreover, a page break is triggered first if necessary (in case automatic
     *                                page breaking is enabled) and, after the call, the current ordinate is moved to
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
     * @param string|int|null $link   the URL or an identifier returned by the <code>addLink()</code> function
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
    ): void;
}
