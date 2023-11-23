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

use App\Pdf\Colors\PdfFillColor;

/**
 * Interface to define chart types.
 *
 * @psalm-type ColorStringType = array{color: PdfFillColor|string, label: string, ...}
 * @psalm-type ColorValueType = array{color: PdfFillColor|string, value: float, ...}
 * @psalm-type BarChartRowType = array{label: string, values: ColorValueType[]}
 * @psalm-type BarChartAxisType = array{min?: float, max?: float, step?: float, formatter?: callable(float): string}
 */
interface PdfChartInterface
{
}
