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

namespace App\Pdf\Colors;

use fpdf\Color\PdfRgbColor;

/**
 * A predefined set of chart colors.
 */
class PdfChartColors
{
    private const COLORS = [
        [54, 162, 235], // blue
        [255, 99, 132], // red
        [255, 159, 64], // orange
        [255, 205, 86], // yellow
        [75, 192, 192], // green
        [153, 102, 255], // purple
        [201, 203, 207], // grey
    ];

    /**
     * The created RGB colors.
     *
     * @var PdfRgbColor[]
     */
    private array $colors = [];

    /**
     * The current color index.
     */
    private int $index = 0;

    /**
     * Gets the next color.
     */
    public function next(): PdfRgbColor
    {
        if (!isset($this->colors[$this->index])) {
            $this->colors[$this->index] = new PdfRgbColor(...self::COLORS[$this->index]);
        }
        $color = $this->colors[$this->index];
        $this->index = ($this->index + 1) % \count(self::COLORS);

        return $color;
    }

    /**
     * Reset the color index.
     */
    public function reset(): void
    {
        $this->index = 0;
    }
}
