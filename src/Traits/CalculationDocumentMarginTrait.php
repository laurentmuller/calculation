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

namespace App\Traits;

use App\Spreadsheet\WorksheetDocument;

/**
 * Trait to get the overall margin format.
 */
trait CalculationDocumentMarginTrait
{
    /**
     * Gets the overall margin format.
     *
     * @param WorksheetDocument $sheet     the worksheet to get the percent format
     * @param float             $minMargin the minimum margin
     */
    protected function getMarginFormat(WorksheetDocument $sheet, float $minMargin): string
    {
        return \sprintf(
            '[Black][=0]%1$s;[Red][<%2$s]%1$s;%1$s',
            $sheet->getPercentFormat(),
            $minMargin
        );
    }
}
