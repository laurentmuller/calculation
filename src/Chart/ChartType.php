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

namespace App\Chart;

/**
 * The chart type enumeration.
 */
enum ChartType: string
{
    /** The column chart type. */
    case TYPE_COLUMN = 'column';
    /** The line chart type. */
    case TYPE_LINE = 'line';
    /** The pie chart type. */
    case TYPE_PIE = 'pie';
    /** The spline chart type. */
    case TYPE_SP_LINE = 'spline';
}
