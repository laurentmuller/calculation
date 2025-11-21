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

use App\Model\CustomerInformation;
use App\Utils\StringUtils;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\Clock\DatePoint;

/**
 * Extends the worksheet class with shortcuts to render cells.
 *
 *  Note: All work sheets are instance of {@link SpreadsheetDocument}.
 */
class WorksheetDocument extends Worksheet
{
    /**
     * The date time format ('dd/mm/yyyy hh:mm').
     */
    private const FORMAT_DATE_TIME = 'dd/mm/yyyy hh:mm';

    /**
     * The identifier format.
     */
    private const FORMAT_ID = '000000';

    /**
     * The integer format.
     */
    private const FORMAT_INT = '#,##0';

    /**
     * The top margins when the customer header is present (21 millimeters).
     */
    private const HEADER_CUSTOMER_MARGIN = 0.83;

    /**
     * The top and bottom margins when header and/or footer is present (12 millimeters).
     */
    private const HEADER_FOOTER_MARGIN = 0.47;

    /**
     * The boolean formats.
     *
     * @var array<string, string>
     */
    private array $booleanFormats = [];

    /**
     * Create a new instance.
     *
     * Sets the page size to A4 and to portrait orientation.
     *
     * @param SpreadsheetDocument|null $parent the optional parent document
     * @param string                   $title  the title
     */
    public function __construct(?SpreadsheetDocument $parent = null, string $title = 'Worksheet')
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
     * Gets style for the given column index.
     *
     * @param int $columnIndex the one-based column index (1 = 'A' - First column)
     */
    public function getColumnStyleFromIndex(int $columnIndex): Style
    {
        $coordinate = $this->stringFromColumnIndex($columnIndex);

        return $this->getStyle($coordinate);
    }

    /**
     * Get parent or null.
     */
    #[\Override]
    public function getParent(): ?SpreadsheetDocument
    {
        /** @var SpreadsheetDocument|null */
        return parent::getParent();
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
     * @param int  $startColumn the one-based index of the first column (1 = 'A' - First column)
     * @param int  $endColumn   the one-based index of the last column
     * @param int  $startRow    the one-based index of the first row (1 = First row)
     * @param ?int $endRow      the one-based index of the last row or null to use the start row
     *
     * @throws Exception if an exception occurs
     */
    public function mergeContent(int $startColumn, int $endColumn, int $startRow, ?int $endRow = null): static
    {
        $this->mergeCells([$startColumn, $startRow, $endColumn, $endRow ?? $startRow]);

        return $this;
    }

    /**
     * Re-bind parent.
     *
     * @throws Exception if the given parent is not an instance of WorksheetDocument
     */
    #[\Override]
    public function rebindParent(Spreadsheet $parent): static
    {
        if (!$parent instanceof SpreadsheetDocument) {
            throw new Exception(\sprintf('%s expected, %s given.', SpreadsheetDocument::class, StringUtils::getDebugType($parent)));
        }

        return parent::rebindParent($parent);
    }

    /**
     * Set the auto-size for the given columns.
     *
     * @param int ...$columnIndexes the one-based column indexes (1 = 'A' - First column)
     */
    public function setAutoSize(int ...$columnIndexes): static
    {
        foreach ($columnIndexes as $columnIndex) {
            $this->getColumnDimensionByColumn($columnIndex)->setAutoSize(true);
        }

        return $this;
    }

    /**
     * Sets the content of the given cell.
     *
     * Do nothing if the value to set is null or is an empty string ('').
     *
     * @param int   $columnIndex the one-based column index (1 = 'A' - First column)
     * @param int   $rowIndex    the one-based row index (1 = First row)
     * @param mixed $value       the value to set
     */
    public function setCellContent(int $columnIndex, int $rowIndex, mixed $value): static
    {
        if (null === $value || '' === $value) {
            return $this;
        }
        if ($value instanceof DatePoint) {
            $value = Date::PHPToExcel($value);
        } elseif (\is_bool($value)) {
            $value = (int) $value;
        }

        return $this->setCellValue([$columnIndex, $rowIndex], $value);
    }

    /**
     * Sets image at the given coordinate.
     *
     * @param string $path        the image path
     * @param string $coordinates the coordinates of the cell (like 'A1')
     * @param int    $width       the image width
     * @param int    $height      the image height
     *
     * @throws Exception if an exception occurs
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
        [$columnIndex, $rowIndex] = Coordinate::indexesFromString($coordinates);
        $columnDimension = $this->getColumnDimensionByColumn($columnIndex);
        if ($width > $columnDimension->getWidth()) {
            $columnDimension->setWidth($width);
        }
        $rowDimension = $this->getRowDimension($rowIndex);
        if ($height > $rowDimension->getRowHeight()) {
            $rowDimension->setRowHeight($height);
        }

        return $this;
    }

    /**
     * Sets a hyperlink for the given cell.
     *
     * Do nothing if the link is an empty string ('').
     *
     * @param int    $columnIndex the one-based column index (1 = 'A' - First column)
     * @param int    $rowIndex    the one-based row index (1 = First row)
     * @param string $link        the hyperlink to set
     * @param string $color       the color of the font or an empty string if none
     * @param bool   $underline   true to set the underlined font
     */
    public function setCellLink(
        int $columnIndex,
        int $rowIndex,
        string $link,
        string $color = Color::COLOR_BLUE,
        bool $underline = false
    ): static {
        if ('' === $link) {
            return $this;
        }

        $cell = $this->getCell([$columnIndex, $rowIndex]);
        $cell->getHyperlink()->setUrl($link);
        if ('' === $color && !$underline) {
            return $this;
        }

        $font = $cell->getStyle()->getFont();
        if ('' !== $color) {
            $font->getColor()->setARGB($color);
        }
        if ($underline) {
            $font->setUnderline($underline);
        }

        return $this;
    }

    /**
     * Set the horizontal alignement for the given column.
     *
     * @param int    $columnIndex         the one-based column index (1 = 'A' - First column)
     * @param string $horizontalAlignment the horizontal alignement (one of the Alignment::HORIZONTAL_*)
     */
    public function setColumnAlignment(int $columnIndex, string $horizontalAlignment): static
    {
        $this->getColumnStyleFromIndex($columnIndex)
            ->getAlignment()
            ->setHorizontal($horizontalAlignment);

        return $this;
    }

    /**
     * Add conditionals to the given column.
     *
     * @param int $columnIndex the one-based column index (1 = 'A' - First column)
     */
    public function setColumnConditional(int $columnIndex, Conditional ...$conditionals): static
    {
        $style = $this->getColumnStyleFromIndex($columnIndex);
        $conditionals = \array_merge($style->getConditionalStyles(), $conditionals);
        $style->setConditionalStyles($conditionals);

        return $this;
    }

    /**
     * Set the width for the given column.
     *
     * The auto-size property is automatically disabled.
     *
     * @param int  $columnIndex the one-based column index (1 = 'A' - First column)
     * @param int  $width       the width to set
     * @param bool $wrapText    true to wrap text
     *
     * @see WorksheetDocument::setWrapText()
     */
    public function setColumnWidth(int $columnIndex, int $width, bool $wrapText = false): static
    {
        $this->getColumnDimensionByColumn($columnIndex)
            ->setAutoSize(false)
            ->setWidth($width);

        return $wrapText ? $this->setWrapText($columnIndex) : $this;
    }

    /**
     * Sets the foreground color for the given column.
     *
     * @param int    $columnIndex   the one-based column index (1 = 'A' - First column)
     * @param string $color         the hexadecimal color or an empty string for black color
     * @param bool   $includeHeader true to set color for all rows; false to skip the first row
     */
    public function setForeground(int $columnIndex, string $color, bool $includeHeader = false): static
    {
        $style = $this->getColumnStyleFromIndex($columnIndex);
        $fontColor = $style->getFont()->getColor();
        if (\strlen($color) > 6) {
            $fontColor->setARGB($color);
        } else {
            $fontColor->setRGB($color);
        }
        if (!$includeHeader) {
            $this->getStyle([$columnIndex, 1])->getFont()->getColor()->setARGB();
        }

        return $this;
    }

    /**
     * Sets the format for the given column index.
     *
     * @param int    $columnIndex the one-based column index ('A' = First column)
     * @param string $format      the format to apply
     */
    public function setFormat(int $columnIndex, string $format): static
    {
        $this->getColumnStyleFromIndex($columnIndex)
            ->getNumberFormat()
            ->setFormatCode($format);

        return $this;
    }

    /**
     * Sets the amount format for the given column.
     *
     * @param int  $columnIndex the one-based column index (1 = 'A' - First column)
     * @param bool $zeroInRed   if true, the red color is used when values are smaller than or equal to 0
     */
    public function setFormatAmount(int $columnIndex, bool $zeroInRed = false): static
    {
        $format = NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1;
        if ($zeroInRed) {
            return $this->setFormat($columnIndex, \sprintf('[Red][<=0]%1$s;%1$s', $format));
        }

        return $this->setFormat($columnIndex, $format);
    }

    /**
     * Sets the boolean format for the given column.
     *
     * @param int    $columnIndex the one-based column index (1 = 'A' - First column)
     * @param string $true        the value to display for <code>true</code>
     * @param string $false       the value to display for <code>false</code>
     * @param bool   $translate   <code>true</code> to translate values
     */
    public function setFormatBoolean(int $columnIndex, string $true, string $false, bool $translate = false): static
    {
        $key = \sprintf('%s-%s', $true, $false);
        if (!\array_key_exists($key, $this->booleanFormats)) {
            if ($translate) {
                $true = $this->trans($true);
                $false = $this->trans($false);
            }
            $true = \str_replace('"', "''", $true);
            $false = \str_replace('"', "''", $false);
            $format = \sprintf('"%s";;"%s";@', $true, $false);
            $this->booleanFormats[$key] = $format;
        } else {
            $format = $this->booleanFormats[$key];
        }

        return $this->setFormat($columnIndex, $format);
    }

    /**
     * Sets the date format ('dd/mm/yyyy') for the given column.
     *
     * @param int $columnIndex the one-based column index (1 = 'A' - First column)
     */
    public function setFormatDate(int $columnIndex): static
    {
        return $this->setFormat($columnIndex, NumberFormat::FORMAT_DATE_DDMMYYYY);
    }

    /**
     * Sets the date and time format ('dd/mm/yyyy hh:mm') for the given column.
     *
     * @param int $columnIndex the one-based column index (1 = 'A' - First column)
     */
    public function setFormatDateTime(int $columnIndex): static
    {
        return $this->setFormat($columnIndex, self::FORMAT_DATE_TIME);
    }

    /**
     * Sets the identifier format ('000000') for the given column.
     *
     * @param int $columnIndex the one-based column index (1 = 'A' - First column)
     */
    public function setFormatId(int $columnIndex): static
    {
        return $this->setFormat($columnIndex, self::FORMAT_ID);
    }

    /**
     * Sets the integer format for the given column.
     *
     * @param int $columnIndex the one-based column index (1 = 'A' - First column)
     */
    public function setFormatInt(int $columnIndex): static
    {
        return $this->setFormat($columnIndex, self::FORMAT_INT);
    }

    /**
     * Sets the percent format for the given column.
     *
     * @param int  $columnIndex the one-based column index (1 = 'A' - First column)
     * @param bool $decimals    true to display 2 decimals ('0.00%'), false if none ('0%').
     */
    public function setFormatPercent(int $columnIndex, bool $decimals = false): static
    {
        return $this->setFormat($columnIndex, $this->getPercentFormat($decimals));
    }

    /**
     * Sets the translated 'Yes/No' boolean format for the given column.
     *
     * @param int $columnIndex the one-based column index (1 = 'A' - First column)
     */
    public function setFormatYesNo(int $columnIndex): static
    {
        return $this->setFormatBoolean($columnIndex, 'common.value_true', 'common.value_false', true);
    }

    /**
     * Sets the headers with bold style and frozen the given row index.
     *
     * Do nothing if headers is an empty array.
     *
     * @param array<string,HeaderFormat> $headers     the headers where the key is the column name to translate
     * @param int                        $columnIndex the one-based starting column index (1 = 'A' - First column)
     * @param int                        $rowIndex    the one-based row index (1 = First row)
     *
     * @return int the given row index + 1
     *
     * @throws Exception if an exception occurs
     */
    public function setHeaders(array $headers, int $columnIndex = 1, int $rowIndex = 1): int
    {
        if ([] === $headers) {
            return $rowIndex;
        }

        $index = $columnIndex;
        foreach ($headers as $id => $header) {
            $header->apply($this, $index);
            $this->getColumnDimensionByColumn($index)->setAutoSize(true);
            $this->setCellContent($index, $rowIndex, $this->trans($id));
            ++$index;
        }

        $this->getStyle([$columnIndex, $rowIndex, $index - 1, $rowIndex])
            ->getFont()
            ->setBold(true);
        $this->freezePane(\sprintf('A%d', $rowIndex + 1));
        $this->getPageSetup()
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setHorizontalCentered(true)
            ->setRowsToRepeatAtTopByStartAndEnd($rowIndex, $rowIndex);

        return $rowIndex + 1;
    }

    /**
     * Sets landscape orientation for the active sheet.
     */
    public function setPageLandscape(): static
    {
        $this->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);

        return $this;
    }

    /**
     * Sets portrait orientation for the active sheet.
     */
    public function setPagePortrait(): static
    {
        $this->getPageSetup()->setOrientation(PageSetup::ORIENTATION_PORTRAIT);

        return $this;
    }

    /**
     * Sets the paper size to A4 for the active sheet.
     */
    public function setPageSizeA4(): static
    {
        $this->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4);

        return $this;
    }

    /**
     * Sets the values of the given row.
     *
     * @param int   $rowIndex    the one-based row index (1 = First row)
     * @param array $values      the values to set
     * @param int   $columnIndex the one-based starting column index (1 = 'A' - First column)
     */
    public function setRowValues(int $rowIndex, array $values, int $columnIndex = 1): static
    {
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
     *                                            certain no formula cells on any worksheet contain
     *                                            references to this worksheet
     * @param bool   $validate                    False to skip validation of the new title. WARNING: This should only
     *                                            be set at parse time (by Readers), where titles can be assumed to be
     *                                            valid.
     */
    #[\Override]
    public function setTitle(string $title, bool $updateFormulaCellReferences = true, bool $validate = true): static
    {
        return parent::setTitle($this->validateTitle($title), $updateFormulaCellReferences, $validate);
    }

    /**
     * Set wrap text for the given column.
     *
     * The auto-size property is automatically disabled.
     *
     * @param int $columnIndex the one-based column index (1 = 'A' - First column)
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
     * @param int $columnIndex the one-based column index (1 = 'A' - First column)
     */
    public function stringFromColumnIndex(int $columnIndex): string
    {
        return Coordinate::stringFromColumnIndex($columnIndex);
    }

    /**
     * Update this header and footer with the given customer information.
     */
    public function updateHeaderFooter(CustomerInformation $customer): static
    {
        $pageMargins = $this->getPageMargins()
            ->setTop(self::HEADER_FOOTER_MARGIN)
            ->setBottom(self::HEADER_FOOTER_MARGIN);

        $header = HeaderFooter::header()
            ->addLeft($this->getTitle(), true)
            ->addRight($customer->getName(), true);
        if ($customer->isPrintAddress()) {
            $header->addRight($customer->getAddress())
                ->addRight($customer->getZipCity());
            $pageMargins->setTop(self::HEADER_CUSTOMER_MARGIN);
        }
        $header->apply($this);

        HeaderFooter::footer()
            ->addPages()
            ->addDateTime()
            ->apply($this);

        return $this;
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
        $invalidChars = self::getInvalidCharacters();
        $title = \str_replace($invalidChars, '', $title);
        if (StringHelper::countCharacters($title) > self::SHEET_TITLE_MAXIMUM_LENGTH) {
            return StringHelper::substring($title, 0, self::SHEET_TITLE_MAXIMUM_LENGTH);
        }

        return $title;
    }
}
