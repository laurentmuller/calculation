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

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Extends the worksheet class with shortcuts to render cells.
 */
class WorksheetDocument extends Worksheet
{
    /**
     * The date time format ('dd/mm/yyyy hh:mm').
     */
    private const FORMAT_DATE_TIME = 'dd/mm/yyyy hh:mm';

    /**
     * The identifier format ('000000').
     */
    private const FORMAT_ID = '000000';

    /**
     * The integer format ('#,##0').
     */
    private const FORMAT_INT = '#,##0';

    /**
     * The boolean formats.
     *
     * @var array<string, string>
     */
    private array $booleanFormats = [];

    /**
     * @param string $title
     */
    public function __construct(SpreadsheetDocument $parent = null, $title = 'Worksheet')
    {
        parent::__construct($parent, $title);
        $this->setPageSizeA4()->setPagePortrait();
    }

    /**
     * Ends render this document by selecting the given cell.
     *
     * @param string $selection the cell to select
     */
    public function finish(string $selection = 'A2'): self
    {
        return $this->setSelectedCell($selection);
    }

    /**
     * Gets style for the given column.
     *
     * @param int $columnIndex the column index ('A' = First column)
     */
    public function getColumnStyle(int $columnIndex): Style
    {
        $coordinate = $this->stringFromColumnIndex($columnIndex);

        return $this->getStyle($coordinate);
    }

    /**
     * Get parent or null.
     */
    public function getParent(): ?SpreadsheetDocument
    {
        /** @psalm-var SpreadsheetDocument|null $parent */
        $parent = parent::getParent();

        return $parent;
    }

    /**
     * Gets the percent format.
     *
     * @param bool $decimals true to display 2 decimals ('0.00%'), false if none ('0%').
     */
    public function getPercentFormat(bool $decimals = false): string
    {
        return $decimals ? NumberFormat::FORMAT_PERCENTAGE_00 : NumberFormat::FORMAT_PERCENTAGE;
    }

    /**
     * Set merge on a cell range by using cell coordinates.
     *
     * @param int  $startColumn the index of the first column ('A' = First column)
     * @param int  $endColumn   the index of the last column
     * @param int  $startRow    the index of first row (1 = First row)
     * @param ?int $endRow      the index of the last cell or null to use the start row
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception if an exception occurs
     */
    public function mergeContent(int $startColumn, int $endColumn, int $startRow, int $endRow = null): static
    {
        $this->mergeCells([$startColumn, $startRow, $endColumn, $endRow ?? $startRow]);

        return $this;
    }

    /**
     * Set the auto-size for the given columns.
     *
     * @param int ...$columnIndexes the column indexes ('A' = First column)
     */
    public function setAutoSize(int ...$columnIndexes): static
    {
        foreach ($columnIndexes as $columnIndex) {
            $name = $this->stringFromColumnIndex($columnIndex);
            $this->getColumnDimension($name)->setAutoSize(true);
        }

        return $this;
    }

    public function setCellContent(int $columnIndex, int $rowIndex, mixed $value): static
    {
        if (null !== $value && '' !== $value) {
            if ($value instanceof \DateTimeInterface) {
                $value = Date::PHPToExcel($value);
            } elseif (\is_bool($value)) {
                $value = (int) $value;
            }
            parent::setCellValue([$columnIndex, $rowIndex], $value);
        }

        return $this;
    }

    /**
     * Sets image at the given coordinate.
     *
     * @param string $path        the image path
     * @param string $coordinates the coordinates (eg: 'A1')
     * @param int    $width       the image width
     * @param int    $height      the image height
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception if an exception occurs
     */
    public function setCellImage(string $path, string $coordinates, int $width, int $height): static
    {
        $drawing = new Drawing();
        $drawing->setPath($path)
            ->setResizeProportional(false)
            ->setCoordinates($coordinates)
            ->setWidth($width)
            ->setHeight($height)
            ->setOffsetX(2)
            ->setOffsetY(2)
            ->setWorksheet($this);
        [$columnIndex, $rowIndex] = Coordinate::coordinateFromString($coordinates);
        $columnDimension = $this->getColumnDimension($columnIndex);
        if ($width > $columnDimension->getWidth()) {
            $columnDimension->setWidth($width);
        }
        $rowDimension = $this->getRowDimension((int) $rowIndex);
        if ($height > $rowDimension->getRowHeight()) {
            $rowDimension->setRowHeight($height);
        }

        return $this;
    }

    /**
     * Add conditionals to the given column.
     *
     * @param int $columnIndex the column index ('A' = First column)
     */
    public function setColumnConditional(int $columnIndex, Conditional ...$conditionals): static
    {
        $style = $this->getColumnStyle($columnIndex);
        $conditionals = \array_merge($style->getConditionalStyles(), $conditionals);
        $style->setConditionalStyles($conditionals);

        return $this;
    }

    /**
     * Set the width for the given column.
     *
     * @param int  $columnIndex the column index ('A' = First column)
     * @param int  $width       the width to set
     * @param bool $wrapText    true to wrap text
     *
     * @see WorksheetDocument::setWrapText()
     */
    public function setColumnWidth(int $columnIndex, int $width, bool $wrapText = false): static
    {
        $name = $this->stringFromColumnIndex($columnIndex);
        $this->getColumnDimension($name)->setWidth($width);

        return $wrapText ? $this->setWrapText($columnIndex) : $this;
    }

    /**
     * Sets the foreground color for the given column.
     *
     * @param int    $columnIndex   the column index ('A' = First column)
     * @param string $color         the hexadecimal color or an empty string ("") for black color
     * @param bool   $includeHeader true to set color for all rows; false to skip the first row
     */
    public function setForeground(int $columnIndex, string $color, bool $includeHeader = false): static
    {
        $name = $this->stringFromColumnIndex($columnIndex);
        $style = $this->getColumnStyle($columnIndex);
        $fontColor = $style->getFont()->getColor();
        if (\strlen($color) > 6) {
            $fontColor->setARGB($color);
        } else {
            $fontColor->setRGB($color);
        }
        if (!$includeHeader) {
            $this->getStyle("{$name}1")->getFont()->getColor()
                ->setARGB();
        }

        return $this;
    }

    public function setFormat(int $columnIndex, string $format): static
    {
        $this->getColumnStyle($columnIndex)
            ->getNumberFormat()
            ->setFormatCode($format);

        return $this;
    }

    /**
     * Sets the amount format ('#,##0.00') for the given column.
     *
     * @param int  $columnIndex the column index ('A' = First column)
     * @param bool $zeroInRed   if true, the red color is used when values are smaller than or equal to 0
     */
    public function setFormatAmount(int $columnIndex, bool $zeroInRed = false): static
    {
        $format = NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;
        if ($zeroInRed) {
            return $this->setFormat($columnIndex, "[Red][<=0]$format;$format");
        }

        return $this->setFormat($columnIndex, $format);
    }

    /**
     * Sets the boolean format for the given column.
     *
     * @param int    $columnIndex the column index ('A' = First column)
     * @param string $true        the value to display when <code>true</code>
     * @param string $false       the value to display when <code>false</code>
     * @param bool   $translate   <code>true</code> to translate values
     */
    public function setFormatBoolean(int $columnIndex, string $true, string $false, bool $translate = false): static
    {
        $key = "$false-$true";
        if (!\array_key_exists($key, $this->booleanFormats)) {
            if ($translate) {
                $true = $this->trans($true);
                $false = $this->trans($false);
            }
            $true = \str_replace('"', "''", $true);
            $false = \str_replace('"', "''", $false);
            $format = "\"$true\";;\"$false\";@";
            $this->booleanFormats[$key] = $format;
        } else {
            $format = $this->booleanFormats[$key];
        }

        return $this->setFormat($columnIndex, $format);
    }

    /**
     * Sets the date format ('dd/mm/yyyy') for the given column.
     *
     * @param int $columnIndex the column index ('A' = First column)
     */
    public function setFormatDate(int $columnIndex): static
    {
        return $this->setFormat($columnIndex, NumberFormat::FORMAT_DATE_DDMMYYYY);
    }

    /**
     * Sets the date and time format ('dd/mm/yyyy hh:mm') for the given column.
     *
     * @param int $columnIndex the column index ('A' = First column)
     */
    public function setFormatDateTime(int $columnIndex): static
    {
        return $this->setFormat($columnIndex, self::FORMAT_DATE_TIME);
    }

    /**
     * Sets the identifier format ('000000') for the given column.
     *
     * @param int $columnIndex the column index ('A' = First column)
     */
    public function setFormatId(int $columnIndex): static
    {
        return $this->setFormat($columnIndex, self::FORMAT_ID);
    }

    /**
     * Sets the integer format ('#,##0') for the given column.
     *
     * @param int $columnIndex the column index ('A' = First column)
     */
    public function setFormatInt(int $columnIndex): static
    {
        return $this->setFormat($columnIndex, self::FORMAT_INT);
    }

    /**
     * Sets the percent format for the given column.
     *
     * @param int  $columnIndex the column index ('A' = First column)
     * @param bool $decimals    true to display 2 decimals ('0.00%'), false if none ('0%').
     */
    public function setFormatPercent(int $columnIndex, bool $decimals = false): static
    {
        return $this->setFormat($columnIndex, $this->getPercentFormat($decimals));
    }

    /**
     * Sets the translated 'Yes/No' boolean format for the given column.
     *
     * @param int $columnIndex the column index ('A' = First column)
     */
    public function setFormatYesNo(int $columnIndex): static
    {
        return $this->setFormatBoolean($columnIndex, 'common.value_true', 'common.value_false', true);
    }

    /**
     * Sets the headers with bold style and frozen first row.
     *
     * @param array<string,HeaderFormat> $headers     the headers where the key is the column name to translate
     * @param int                        $columnIndex the starting column index ('A' = First column)
     * @param int                        $rowIndex    the row index (1 = First row)
     *
     * @return int this function return the given row index + 1
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception if an exception occurs
     */
    public function setHeaders(array $headers, int $columnIndex = 1, int $rowIndex = 1): int
    {
        $index = $columnIndex;
        foreach ($headers as $id => $header) {
            $header->apply($this, $index);
            $name = $this->stringFromColumnIndex($index);
            $this->getColumnDimension($name)->setAutoSize(true);
            $this->setCellValue("$name$rowIndex", $this->trans($id));
            ++$index;
        }

        $firstName = $this->stringFromColumnIndex($columnIndex);
        $lastName = $this->stringFromColumnIndex($columnIndex + \count($headers) - 1);
        $this->getStyle("$firstName$rowIndex:$lastName$rowIndex")->getFont()->setBold(true);
        $this->freezePane(\sprintf('A%d', $rowIndex + 1));
        $this->getPageSetup()
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setHorizontalCentered(true)
            ->setRowsToRepeatAtTopByStartAndEnd($rowIndex, $rowIndex);

        return $rowIndex + 1;
    }

    /**
     * Sets the margins.
     *
     * @param float $margins the margins to set
     */
    public function setMargins(float $margins): static
    {
        $pageMargins = $this->getPageMargins();
        $pageMargins->setTop($margins)
            ->setBottom($margins)
            ->setLeft($margins)
            ->setRight($margins);

        return $this;
    }

    /**
     * Sets landscape orientation for the active sheet.
     */
    public function setPageLandscape(): static
    {
        return $this->setPageOrientation(PageSetup::ORIENTATION_LANDSCAPE);
    }

    /**
     * Set the page orientation (default, portait or landscape).
     *
     * @psalm-param PageSetup::ORIENTATION_* $orientation
     */
    public function setPageOrientation(string $orientation): static
    {
        switch ($orientation) {
            case PageSetup::ORIENTATION_DEFAULT:
            case PageSetup::ORIENTATION_PORTRAIT:
            case PageSetup::ORIENTATION_LANDSCAPE:
                $this->getPageSetup()->setOrientation($orientation);
                break;
        }

        return $this;
    }

    /**
     * Sets portrait orientation for the active sheet.
     */
    public function setPagePortrait(): static
    {
        return $this->setPageOrientation(PageSetup::ORIENTATION_PORTRAIT);
    }

    /**
     * Sets the paper size for the active sheet.
     *
     * @param int $size the paper size that must be one of PageSetup paper size constant
     *
     * @psalm-param PageSetup::PAPERSIZE_* $size
     */
    public function setPageSize(int $size): static
    {
        $this->getPageSetup()->setPaperSize($size);

        return $this;
    }

    /**
     * Sets the paper size to A4 for the active sheet.
     */
    public function setPageSizeA4(): static
    {
        return $this->setPageSize(PageSetup::PAPERSIZE_A4);
    }

    /**
     * Sets the values of the given row.
     *
     * @param int   $rowIndex    the row index (1 = First row)
     * @param array $values      the values to set
     * @param int   $columnIndex the starting column index ('A' = First column)
     */
    public function setRowValues(int $rowIndex, array $values, int $columnIndex = 1): static
    {
        /** @psalm-var mixed $value*/
        foreach ($values as $value) {
            $this->setCellContent($columnIndex++, $rowIndex, $value);
        }

        return $this;
    }

    /**
     * Set title.
     *
     * @param string $title                       String containing the dimension of this worksheet
     * @param bool   $updateFormulaCellReferences Flag indicating whether cell references in formulae should
     *                                            be updated to reflect the new sheet name.
     *                                            This should be left as the default true, unless you are
     *                                            certain that no formula cells on any worksheet contain
     *                                            references to this worksheet
     * @param bool   $validate                    False to skip validation of new title. WARNING: This should only be set
     *                                            at parse time (by Readers), where titles can be assumed to be valid.
     *
     * @return $this
     */
    public function setTitle($title, $updateFormulaCellReferences = true, $validate = true): static
    {
        return parent::setTitle($this->validateTitle($title), $updateFormulaCellReferences, $validate);
    }

    /**
     * Set wrap text for the given column. The auto-size is automatically disabled.
     *
     * @param int $columnIndex the column index ('A' = First column)
     */
    public function setWrapText(int $columnIndex): static
    {
        $name = $this->stringFromColumnIndex($columnIndex);
        $this->getColumnDimension($name)->setAutoSize(false);
        $this->getStyle($name)->getAlignment()->setWrapText(true);

        return $this;
    }

    /**
     * Get the string from the given column index.
     *
     * @param int $columnIndex the column index ('A' = First column)
     */
    public function stringFromColumnIndex(int $columnIndex): string
    {
        return Coordinate::stringFromColumnIndex($columnIndex);
    }

    private function trans(string $id): string
    {
        return $this->getParent()?->trans($id) ?? $id;
    }

    /**
     * Validate the worksheet title.
     */
    private function validateTitle(string $title): string
    {
        /** @var string[] $invalidChars */
        $invalidChars = self::getInvalidCharacters();
        $title = \str_replace($invalidChars, '', $title);
        if (StringHelper::countCharacters($title) > self::SHEET_TITLE_MAXIMUM_LENGTH) {
            return StringHelper::substring($title, 0, self::SHEET_TITLE_MAXIMUM_LENGTH);
        }

        return $title;
    }
}
