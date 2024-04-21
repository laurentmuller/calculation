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

namespace App\Pdf\Events;

use App\Pdf\Interfaces\PdfLabelTextListenerInterface;
use App\Pdf\PdfLabelDocument;

/**
 * The event raised when a label text must be rendered.
 *
 * @see PdfLabelTextListenerInterface
 */
class PdfLabelTextEvent
{
    /**
     * @param PdfLabelDocument $parent the parent's document
     * @param string           $text   the text to output
     * @param int              $index  the text index (zero-based line index)
     * @param int              $lines  the number of lines
     * @param float            $width  the output width
     * @param float            $height the output height (line height)
     */
    public function __construct(
        public PdfLabelDocument $parent,
        public string $text,
        public int $index,
        public int $lines,
        public float $width,
        public float $height
    ) {
    }
}
