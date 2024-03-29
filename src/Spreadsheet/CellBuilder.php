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

use App\Utils\StringUtils;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Builder to set a cell value and style.
 */
class CellBuilder
{
    /**
     * The date time format ('dd/mm/yyyy hh:mm').
     */
    final public const FORMAT_DATE_TIME = 'dd/mm/yyyy hh:mm';

    /**
     * The identifier format ('000000').
     */
    final public const FORMAT_ID = '000000';

    /**
     * The integer format ('#,##0').
     */
    final public const FORMAT_INT = '#,##0';

    /**
     * The bold font.
     */
    private bool $bold = false;

    /**
     * The number format.
     */
    private string $format = '';

    /**
     * The horizontal alignment.
     */
    private string $horizontal = '';

    /**
     * The left indent.
     */
    private int $indent = 0;

    /**
     * The vertical alignment.
     */
    private string $vertical = '';

    /**
     * @param Worksheet $sheet the sheet used to set cell values and styles
     */
    public function __construct(private readonly Worksheet $sheet)
    {
    }

    /**
     * Sets the cell value and style at the given coordinate.
     *
     * @param string $coordinate the coordinate of the cell, eg: 'A1'
     * @param mixed  $value      the value of the cell
     */
    public function apply(string $coordinate, mixed $value): self
    {
        $style = $this->sheet->getStyle($coordinate);
        $this->updateBold($style)
            ->updateIndent($style)
            ->updateAlignment($style)
            ->updateFormat($style);
        if (null !== $value) {
            if ($value instanceof \DateTimeInterface) {
                $value = Date::PHPToExcel($value);
            } elseif (\is_bool($value)) {
                $value = $value ? 1 : 0;
            }
            $this->sheet->setCellValue($coordinate, $value);
        }

        return $this;
    }

    /**
     * Sets the cell value and style at the given coordinate.
     *
     * @param int   $column the column index (A = 1)
     * @param int   $row    the row index (1 = First row)
     * @param mixed $value  the value of the cell
     */
    public function applyByColumnAndRow(int $column, int $row, mixed $value): self
    {
        $name = Coordinate::stringFromColumnIndex($column);
        $coordinate = \sprintf('%s%d', $name, $row);

        return $this->apply($coordinate, $value);
    }

    public function bold(bool $bold = true): self
    {
        $this->bold = $bold;

        return $this;
    }

    public function format(string $format = ''): self
    {
        $this->format = $format;

        return $this;
    }

    public function formatAmount(): self
    {
        return $this->horizontalRight()
            ->format(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
    }

    public function formatDate(): self
    {
        return $this->horizontalCenter()
            ->format(NumberFormat::FORMAT_DATE_DDMMYYYY);
    }

    public function formatDateTime(): self
    {
        return $this->horizontalCenter()
            ->format(self::FORMAT_DATE_TIME);
    }

    public function formatId(): self
    {
        return $this->horizontalCenter()
            ->format(self::FORMAT_ID);
    }

    public function formatInt(): self
    {
        return $this->horizontalRight()
            ->format(self::FORMAT_INT);
    }

    public function formatPercent(bool $decimals = false): self
    {
        $format = $decimals ? NumberFormat::FORMAT_PERCENTAGE_00 : NumberFormat::FORMAT_PERCENTAGE;

        return $this->horizontalRight()->format($format);
    }

    public function horizontal(string $horizontal = ''): self
    {
        $this->horizontal = $horizontal;

        return $this;
    }

    public function horizontalCenter(): self
    {
        return $this->horizontal(Alignment::HORIZONTAL_CENTER);
    }

    public function horizontalRight(): self
    {
        return $this->horizontal(Alignment::HORIZONTAL_RIGHT);
    }

    public function indent(int $indent = 0): self
    {
        $this->indent = $indent;

        return $this;
    }

    public function reset(): self
    {
        return $this->bold(false)
            ->horizontal()
            ->vertical()
            ->format()
            ->indent();
    }

    public function vertical(string $vertical = ''): self
    {
        $this->vertical = $vertical;

        return $this;
    }

    public function verticalBottom(): self
    {
        return $this->vertical(Alignment::VERTICAL_BOTTOM);
    }

    public function verticalCenter(): self
    {
        return $this->vertical(Alignment::VERTICAL_CENTER);
    }

    public function verticalTop(): self
    {
        return $this->vertical(Alignment::VERTICAL_TOP);
    }

    private function updateAlignment(Style $style): self
    {
        if (StringUtils::isString($this->horizontal)) {
            $style->getAlignment()->setHorizontal($this->horizontal);
        }
        if (StringUtils::isString($this->vertical)) {
            $style->getAlignment()->setVertical($this->vertical);
        }

        return $this;
    }

    private function updateBold(Style $style): self
    {
        if ($this->bold) {
            $style->getFont()->setBold(true);
        }

        return $this;
    }

    private function updateFormat(Style $style): void
    {
        if (StringUtils::isString($this->format)) {
            $style->getNumberFormat()->setFormatCode($this->format);
        }
    }

    private function updateIndent(Style $style): self
    {
        if ($this->indent > 0) {
            $style->getAlignment()->setIndent($this->indent);
        }

        return $this;
    }
}
