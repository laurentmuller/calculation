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
 * Define the column alignement and format.
 */
class HeaderFormat
{
    public function __construct(
        public ?string $horizontal = null,
        public ?string $vertical = null,
        public ColumnFormat|string|null $format = null
    ) {
    }

    public static function amount(string $vertical = null): self
    {
        return self::create(ColumnFormat::AMOUNT, $vertical);
    }

    public static function amountZero(string $vertical = null): self
    {
        return self::create(ColumnFormat::AMOUNT_ZERO, $vertical);
    }

    /**
     * Apply this alignements and format to the given column.
     *
     * @param WorksheetDocument $parent      the worksheet to update
     * @param int               $columnIndex the column index ('A' = First column)
     */
    public function apply(WorksheetDocument $parent, int $columnIndex): void
    {
        $alignment = $parent->getColumnStyle($columnIndex)->getAlignment();
        if (null !== $this->horizontal && Alignment::HORIZONTAL_GENERAL !== $this->horizontal) {
            $alignment->setHorizontal($this->horizontal);
        }
        if (null !== $this->vertical && Alignment::VERTICAL_BOTTOM !== $this->vertical) {
            $alignment->setVertical($this->vertical);
        }

        if ($this->format instanceof ColumnFormat) {
            $this->format->apply($parent, $columnIndex);
        } elseif (\is_string($this->format)) {
            $parent->setFormat($columnIndex, $this->format);
        }
    }

    public static function center(string $vertical = null): self
    {
        return new self(Alignment::HORIZONTAL_CENTER, $vertical);
    }

    public static function create(ColumnFormat $format, string $vertical = null): self
    {
        return new self($format->alignment(), $vertical, $format);
    }

    public static function date(string $vertical = null): self
    {
        return self::create(ColumnFormat::DATE, $vertical);
    }

    public static function dateTime(string $vertical = null): self
    {
        return self::create(ColumnFormat::DATE_TIME, $vertical);
    }

    public static function id(string $vertical = null): self
    {
        return self::create(ColumnFormat::ID, $vertical);
    }

    public static function instance(string $vertical = null): self
    {
        return new self(vertical: $vertical);
    }

    public static function int(string $vertical = null): self
    {
        return self::create(ColumnFormat::INT, $vertical);
    }

    public static function left(string $vertical = null): self
    {
        return new self(Alignment::HORIZONTAL_LEFT, $vertical);
    }

    public static function percent(string $vertical = null): self
    {
        return self::create(ColumnFormat::PERCENT, $vertical);
    }

    public static function percentCustom(string $format, string $vertical = null): self
    {
        return new self(ColumnFormat::PERCENT->alignment(), $vertical, $format);
    }

    public static function percentDecimals(string $vertical = null): self
    {
        return self::create(ColumnFormat::PERCENT_DECIMALS, $vertical);
    }

    public static function right(string $vertical = null): self
    {
        return new self(Alignment::HORIZONTAL_RIGHT, $vertical);
    }

    public static function yesNo(string $vertical = null): self
    {
        return self::create(ColumnFormat::YES_NO, $vertical);
    }
}
