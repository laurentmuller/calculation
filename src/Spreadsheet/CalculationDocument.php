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

use App\Controller\AbstractController;
use App\Entity\Calculation;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Spreadsheet document for a calculation.
 */
class CalculationDocument extends AbstractDocument
{
    /**
     * The header background color.
     */
    private const COLOR_BACKGROUND = 'F5F5F5';

    /**
     * The cell border color.
     */
    private const COLOR_BORDER = 'DDDDDD';

    /**
     * The active sheet.
     */
    private ?Worksheet $sheet = null;

    /**
     * Constructor.
     *
     * @param AbstractController $controller  the parent controller
     * @param Calculation        $calculation the calculation to render
     */
    public function __construct(AbstractController $controller, private readonly Calculation $calculation)
    {
        parent::__construct($controller);
    }

    /**
     * {@inheritDoc}
     */
    public function render(): bool
    {
        $emptyRows = [];
        $calculation = $this->calculation;

        // title
        $id = $calculation->getFormattedId();
        $title = $this->trans('calculation.edit.title', ['%id%' => $id]);
        $this->start($title);
        $this->sheet = $this->getActiveSheet();

        // id
        $row = 1;
        $this->mergeCells(1, 5, $row)
            ->renderBold(1, $row, $title, 0, Alignment::HORIZONTAL_CENTER)
            ->fillBackground($row);
        ++$row;

        // customer and state
        $this->mergeCells(1, 3, $row)
            ->renderBold(1, $row, $calculation->getCustomer())
            ->mergeCells(4, 5, $row)
            ->renderBold(4, $row, $calculation->getStateCode(), 0, Alignment::HORIZONTAL_RIGHT)
            ->fillBackground($row);
        ++$row;

        // description and date
        $this->mergeCells(1, 3, $row)
            ->renderBold(1, $row, $calculation->getDescription())
            ->mergeCells(4, 5, $row)
            ->renderBold(4, $row, Date::PHPToExcel($calculation->getDate()), 0, Alignment::HORIZONTAL_RIGHT, NumberFormat::FORMAT_DATE_DDMMYYYY)
            ->fillBackground($row);
        ++$row;

        // space
        $this->mergeCells(1, 5, $row);
        $emptyRows[] = $row;
        ++$row;

        // empty?
        if ($calculation->isEmpty()) {
            $this->mergeCells(1, 5, $row)
                ->renderText(1, $row, $this->trans('calculation.edit.empty'))
                ->fillBackground($row);

            return $this->renderEnd($row, $emptyRows);
        }

        // items headers
        $this->renderHeaders($row);
        ++$row;

        // groups, categories and items
        foreach ($calculation->getGroups() as $group) {
            $this->mergeCells(1, 5, $row)
                ->renderBold(1, $row, $group->getCode());
            ++$row;

            foreach ($group->getCategories() as $category) {
                $this->mergeCells(1, 5, $row)
                    ->renderBold(1, $row, $category->getCode(), 1);
                ++$row;

                foreach ($category->getItems() as $item) {
                    $this->renderText(1, $row, $item->getDescription(), 1)
                        ->renderText(2, $row, $item->getUnit())
                        ->renderAmount(3, $row, $item->getPrice())
                        ->renderAmount(4, $row, $item->getQuantity())
                        ->renderAmount(5, $row, $item->getTotal());
                    ++$row;
                }
            }
        }

        // items total
        $this->mergeCells(1, 4, $row)
            ->renderBold(1, $row, $this->trans('calculation.fields.itemsTotal'))
            ->renderAmount(5, $row, $calculation->getItemsTotal(), true)
            ->fillBackground($row);
        ++$row;

        // space
        $this->mergeCells(1, 5, $row);
        $emptyRows[] = $row;
        ++$row;

        // total by groups
        $this->renderBold(1, $row, $this->trans('calculation.edit.panel_resume'))
            ->renderBold(2, $row, $this->trans('calculationgroup.fields.amount'), 0, Alignment::HORIZONTAL_RIGHT)
            ->mergeCells(3, 4, $row)
            ->renderBold(3, $row, $this->trans('group.fields.margins'), 0, Alignment::HORIZONTAL_CENTER)
            ->renderBold(5, $row, $this->trans('calculation.fields.total'), 0, Alignment::HORIZONTAL_RIGHT)
            ->fillBackground($row);
        ++$row;

        foreach ($calculation->getGroups() as $group) {
            $this->renderText(1, $row, $group->getCode())
                ->renderAmount(2, $row, $group->getAmount())
                ->renderPercent(3, $row, $group->getMargin())
                ->renderAmount(4, $row, $group->getMarginAmount())
                ->renderAmount(5, $row, $group->getTotal());
            ++$row;
        }

        // margins total
        $this->renderBold(1, $row, $this->trans('calculation.fields.marginTotal'))
            ->renderAmount(2, $row, $calculation->getItemsTotal())
            ->renderPercent(3, $row, $calculation->getGroupsMargin())
            ->renderAmount(4, $row, $calculation->getGroupsMarginAmount())
            ->renderAmount(5, $row, $calculation->getGroupsTotal(), true)
            ->fillBackground($row);
        ++$row;

        // global margin
        $this->mergeCells(1, 2, $row)
            ->renderText(1, $row, $this->trans('calculation.fields.globalMargin'))
            ->renderPercent(3, $row, $calculation->getGlobalMargin())
            ->mergeCells(4, 5, $row)
            ->renderAmount(4, $row, $calculation->getGlobalMarginAmount());
        ++$row;

        if (!empty($calculation->getUserMargin())) {
            // total net
            $this->mergeCells(1, 4, $row)
                ->renderBold(1, $row, $this->trans('calculation.fields.totalNet'))
                ->renderAmount(5, $row, $calculation->getTotalNet(), true)
                ->fillBackground($row);
            ++$row;

            // user margin
            $this->mergeCells(1, 2, $row)
                ->renderText(1, $row, $this->trans('calculation.fields.userMargin'))
                ->renderPercent(3, $row, $calculation->getUserMargin())
                ->mergeCells(4, 5, $row)
                ->renderAmount(4, $row, $calculation->getUserMarginAmount());
            ++$row;
        }

        // overall total
        $this->renderBold(1, $row, $this->trans('calculation.fields.overallTotal'))
            ->renderAmount(2, $row, $calculation->getItemsTotal(), true)
            ->renderPercent(3, $row, $calculation->getOverallMargin(), true, $this->getMarginFormat())
            ->renderAmount(4, $row, $calculation->getOverallMarginAmount(), true)
            ->renderAmount(5, $row, $calculation->getOverallTotal(), true)
            ->fillBackground($row);

        return $this->renderEnd($row, $emptyRows);
    }

    /**
     * Fill the background for the given row.
     *
     * @param int $row the row to fill (1 = First row)
     */
    private function fillBackground(int $row): self
    {
        if (null !== $this->sheet) {
            /** @psalm-var string $coordinate */
            $coordinate = "A$row:E$row";
            $this->sheet->getStyle($coordinate)
                ->getFill()->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB(self::COLOR_BACKGROUND);
        }

        return $this;
    }

    /**
     * Gets the overall margin format.
     */
    private function getMarginFormat(): string
    {
        $minMargin = $this->controller->getApplication()->getMinMargin();
        $format = NumberFormat::FORMAT_PERCENTAGE;

        return "[Red][<$minMargin]$format;$format";
    }

    /**
     * Merge cells.
     *
     * @param int $startColumn the index of the first column (A = 1)
     * @param int $endColumn   the index of the last column
     * @param int $row         the row index (1 = First row)
     */
    private function mergeCells(int $startColumn, int $endColumn, int $row): self
    {
        $this->sheet?->mergeCellsByColumnAndRow($startColumn, $row, $endColumn, $row);

        return $this;
    }

    /**
     * Set a cell value with the amount format.
     *
     * @param int   $column the column index (A = 1)
     * @param int   $row    the row index (1 = First row)
     * @param mixed $value  the value of the cell
     * @param bool  $bold   true to set the bold font
     */
    private function renderAmount(int $column, int $row, mixed $value, bool $bold = false): self
    {
        return $this->renderCell(
            $column,
            $row,
            $value,
            $bold,
            0,
            Alignment::HORIZONTAL_RIGHT,
            NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
        );
    }

    /**
     * Set a cell value with the bold font.
     *
     * @param int    $column    the column index (A = 1)
     * @param int    $row       the row index (1 = First row)
     * @param mixed  $value     the value of the cell
     * @param int    $indent    the horizontal indent
     * @param string $alignment the horizontal alignment
     * @param string $format    the number format
     */
    private function renderBold(int $column, int $row, mixed $value, int $indent = 0, string $alignment = '', string $format = ''): self
    {
        return $this->renderCell($column, $row, $value, true, $indent, $alignment, $format);
    }

    /**
     * Set a cell value.
     *
     * @param int    $column    the column index (A = 1)
     * @param int    $row       the row index (1 = First row)
     * @param mixed  $value     the value of the cell
     * @param bool   $bold      true to set the bold font
     * @param int    $indent    the horizontal indent
     * @param string $alignment the horizontal alignment
     * @param string $format    the number format
     */
    private function renderCell(int $column, int $row, mixed $value, bool $bold = false, int $indent = 0, string $alignment = '', string $format = ''): self
    {
        $coordinate = $this->stringFromColumnAndRowIndex($column, $row);
        if (null !== $this->sheet) {
            $style = $this->sheet->getStyle($coordinate);
            if ($bold) {
                $style->getFont()->setBold(true);
            }
            if ($indent > 0) {
                $style->getAlignment()->setIndent($indent);
            }
            if (!empty($alignment)) {
                $style->getAlignment()->setHorizontal($alignment);
            }
            if (!empty($format)) {
                $style->getNumberFormat()->setFormatCode($format);
            }
            $this->sheet->setCellValue($coordinate, $value);
        }

        return $this;
    }

    /**
     * Render the end of this document.
     *
     * @param int   $lastRow   the last row index
     * @param int[] $emptyRows the empty rows indexes
     *
     * @return bool this function return always true
     */
    private function renderEnd(int $lastRow, array $emptyRows): bool
    {
        if (null !== $this->sheet) {
            // set borders to all cells
            $this->sheet->getStyle("A1:E$lastRow")
                ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)
                ->getColor()->setARGB(self::COLOR_BORDER);

            // remove left and right borders for empty rows
            foreach ($emptyRows as $emptyRow) {
                $this->sheet->getStyle("A$emptyRow")->getBorders()
                    ->getLeft()->setBorderStyle(Border::BORDER_NONE);
                $this->sheet->getStyle("E$emptyRow")->getBorders()
                    ->getRight()->setBorderStyle(Border::BORDER_NONE);
            }

            // column's width
            $this->sheet->getColumnDimension('A')->setWidth(8.5, 'cm');
            foreach (\range('B', 'E') as $column) {
                $this->sheet->getColumnDimension($column)->setWidth(2.0, 'cm');
            }

            // page setup
            $this->sheet->getPageSetup()
                ->setFitToWidth(1)
                ->setFitToHeight(0)
                ->setHorizontalCentered(true)
                ->setRowsToRepeatAtTopByStartAndEnd(1, 4);

            $this->sheet->setPrintGridlines(false)
                ->setShowGridlines(false);

            $this->finish('A1');
        }

        return true;
    }

    /**
     * Render the headers for items.
     *
     * @param int $row the row index (1 = First row)
     */
    private function renderHeaders(int $row): self
    {
        return $this->renderBold(1, $row, $this->trans('calculationitem.fields.description'))
            ->renderBold(2, $row, $this->trans('calculationitem.fields.unit'))
            ->renderBold(3, $row, $this->trans('calculationitem.fields.price'), 0, Alignment::HORIZONTAL_RIGHT)
            ->renderBold(4, $row, $this->trans('calculationitem.fields.quantity'), 0, Alignment::HORIZONTAL_RIGHT)
            ->renderBold(5, $row, $this->trans('calculationitem.fields.total'), 0, Alignment::HORIZONTAL_RIGHT)
            ->fillBackground($row);
    }

    /**
     * Set a cell value with the percentage format.
     *
     * @param int    $column the column index (A = 1)
     * @param int    $row    the row index (1 = First row)
     * @param float  $value  the value of the cell
     * @param bool   $bold   true to set the bold font
     * @param string $format the percent format
     */
    private function renderPercent(int $column, int $row, float $value, bool $bold = false, string $format = NumberFormat::FORMAT_PERCENTAGE): self
    {
        return $this->renderCell($column, $row, $value, $bold, 0, Alignment::HORIZONTAL_RIGHT, $format);
    }

    /**
     * Set a cell value .
     *
     * @param int     $column the column index (A = 1)
     * @param int     $row    the row index (1 = First row)
     * @param ?string $value  the value of the cell
     * @param int     $indent the horizontal indent
     */
    private function renderText(int $column, int $row, ?string $value, int $indent = 0): self
    {
        return $this->renderCell($column, $row, $value, false, $indent);
    }
}
