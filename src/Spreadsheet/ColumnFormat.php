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

namespace App\Spreadsheet;

use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Column format enumeration.
 */
enum ColumnFormat
{
    /**
     * The amount format ('#,##0.00').
     */
    case AMOUNT;
    /**
     * The amount format ('#,##0.00') and the red foreground color when values
     * are smaller than or equal to 0.
     */
    case AMOUNT_ZERO;

    /**
     * The date format ('dd/mm/yyyy').
     */
    case DATE;
    /**
     * The date and time format ('dd/mm/yyyy hh:mm').
     */
    case DATE_TIME;
    /**
     * The identifier format ('000000').
     */
    case ID;
    /**
     * The integer format ('#,##0').
     */
    case INT;
    /**
     * The percent format without decimal ('0%').
     */
    case PERCENT;
    /**
     * The percent format with 2 decimals ('0.00%').
     */
    case PERCENT_DECIMALS;
    /**
     * The translated 'Yes/No' boolean format.
     */
    case YES_NO;

    /**
     * Gets the horizontal alignment.
     */
    public function alignment(): string
    {
        return match ($this) {
            self::AMOUNT,
            self::AMOUNT_ZERO,
            self::INT,
            self::PERCENT,
            self::PERCENT_DECIMALS => Alignment::HORIZONTAL_RIGHT,
            self::DATE,
            self::DATE_TIME,
            self::ID,
            self::YES_NO => Alignment::HORIZONTAL_CENTER
        };
    }

    /**
     * Apply this format to the given column.
     *
     * @param WorksheetDocument $parent      the worksheet to update
     * @param int               $columnIndex the column index ('A' = First column)
     */
    public function apply(WorksheetDocument $parent, int $columnIndex): void
    {
        match ($this) {
            self::AMOUNT => $parent->setFormatAmount($columnIndex),
            self::AMOUNT_ZERO => $parent->setFormatAmount($columnIndex, true),
            self::DATE => $parent->setFormatDate($columnIndex),
            self::DATE_TIME => $parent->setFormatDateTime($columnIndex),
            self::ID => $parent->setFormatId($columnIndex),
            self::INT => $parent->setFormatInt($columnIndex),
            self::PERCENT => $parent->setFormatPercent($columnIndex),
            self::PERCENT_DECIMALS => $parent->setFormatPercent($columnIndex, true),
            self::YES_NO => $parent->setFormatYesNo($columnIndex),
        };
    }
}
