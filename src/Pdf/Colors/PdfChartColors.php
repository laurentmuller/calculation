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
class PdfChartColors implements \Countable
{
    private const array COLORS = [
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
     * Gets the number of predefined colors.
     */
    #[\Override]
    public function count(): int
    {
        return \count(self::COLORS);
    }

    /**
     * Gets the next color.
     */
    public function next(): PdfRgbColor
    {
        $color = $this->colors[$this->index] ??= new PdfRgbColor(...self::COLORS[$this->index]);
        $this->index = ++$this->index % $this->count();

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
