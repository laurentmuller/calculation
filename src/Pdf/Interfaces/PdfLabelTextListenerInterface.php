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

use App\Pdf\PdfLabelDocument;

/**
 * Class implementing this interface handle the draw label texts event.
 */
interface PdfLabelTextListenerInterface
{
    /**
     * Called when a line must be drawn within the label.
     *
     * @param PdfLabelDocument $parent the parent's document
     * @param string           $text   the text to output
     * @param int              $index  the text index (0 based line index)
     * @param int              $lines  the number of lines
     * @param float            $width  the output width
     * @param float            $height the output height (line height)
     *
     * @return bool true if listener handle the draw function; false to call the default behavior
     */
    public function drawLabelText(
        PdfLabelDocument $parent,
        string $text,
        int $index,
        int $lines,
        float $width,
        float $height
    ): bool;
}
