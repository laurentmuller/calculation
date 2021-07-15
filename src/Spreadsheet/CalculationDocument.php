<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Spreadsheet;

use App\Controller\AbstractController;
use App\Entity\Calculation;
use App\Entity\CalculationCategory;
use App\Entity\CalculationGroup;
use App\Entity\CalculationItem;
use App\Util\FormatUtils;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Excel document for a calculation.
 *
 * @author Laurent Muller
 */
class CalculationDocument extends AbstractDocument
{
    private const COLOR_BACKGROUND = 'F5F5F5';

    private const COLOR_BORDER = 'DDDDDD';

    private Calculation $calculation;

    private ?Worksheet $sheet = null;

    /**
     * Constructor.
     *
     * @param AbstractController $controller  the parent controller
     * @param Calculation        $calculation the calculation to render
     */
    public function __construct(AbstractController $controller, Calculation $calculation)
    {
        parent::__construct($controller);
        $this->calculation = $calculation;
    }

    /**
     * {@inheritDoc}
     */
    public function render(): bool
    {
        $calculation = $this->calculation;
        $spaces = [];

        // title
        $id = FormatUtils::formatId($calculation->getId());
        $title = $this->trans('calculation.edit.title', ['%id%' => $id]);
        $this->start($title);
        $this->sheet = $this->getActiveSheet();

        // id
        $row = 1;
        $this->mergeCells(1, 5, $row);
        $this->renderBold(1, $row, $title, 0, Alignment::HORIZONTAL_CENTER);
        $this->fillBackground($row);
        ++$row;

        // customer and state
        $this->mergeCells(1, 3, $row);
        $this->renderBold(1, $row, $calculation->getCustomer());
        $this->mergeCells(4, 5, $row);
        $this->renderBold(4, $row, $calculation->getStateCode(), 0, Alignment::HORIZONTAL_RIGHT);
        $this->fillBackground($row);
        ++$row;

        // descripton and date
        $this->mergeCells(1, 3, $row);
        $this->renderBold(1, $row, $calculation->getDescription());
        $this->mergeCells(4, 5, $row);
        $this->renderBold(4, $row, Date::PHPToExcel($calculation->getDate()), 0, Alignment::HORIZONTAL_RIGHT, NumberFormat::FORMAT_DATE_DDMMYYYY);
        $this->fillBackground($row);
        ++$row;

        // space
        $this->mergeCells(1, 5, $row);
        $spaces[] = $row;
        ++$row;

        // empty?
        if ($calculation->isEmpty()) {
            $this->mergeCells(1, 5, $row);
            $this->renderText(1, $row, $this->trans('calculation.edit.empty'));
            $this->fillBackground($row);

            return $this->renderEnd($row, $spaces);
        }

        // items headers
        $this->renderHeaders($row);
        ++$row;

        // groups, categories and items
        /** @var CalculationGroup $group */
        foreach ($calculation->getGroups() as $group) {
            $this->mergeCells(1, 5, $row);
            $this->renderBold(1, $row, $group->getCode());
            ++$row;

            /** @var CalculationCategory $category */
            foreach ($group->getCategories() as $category) {
                $this->mergeCells(1, 5, $row);
                $this->renderBold(1, $row, $category->getCode(), 1);
                ++$row;

                /** @var CalculationItem $item */
                foreach ($category->getItems() as $item) {
                    $this->renderText(1, $row, $item->getDescription(), 1);
                    $this->renderText(2, $row, $item->getUnit());
                    $this->renderAmount(3, $row, $item->getPrice());
                    $this->renderAmount(4, $row, $item->getQuantity());
                    $this->renderAmount(5, $row, $item->getTotal());
                    ++$row;
                }
            }
        }

        // items total
        $this->mergeCells(1, 4, $row);
        $this->renderBold(1, $row, $this->trans('calculation.fields.itemsTotal'));
        $this->renderAmount(5, $row, $calculation->getItemsTotal(), true);
        $this->fillBackground($row);
        ++$row;

        // space
        $this->mergeCells(1, 5, $row);
        $spaces[] = $row;
        ++$row;

        // total by groups
        $this->renderBold(1, $row, $this->trans('calculation.edit.panel_resume'));
        $this->renderBold(2, $row, $this->trans('calculationgroup.fields.amount'), 0, Alignment::HORIZONTAL_RIGHT);
        $this->mergeCells(3, 4, $row);
        $this->renderBold(3, $row, $this->trans('group.fields.margins'), 0, Alignment::HORIZONTAL_CENTER);
        $this->renderBold(5, $row, $this->trans('calculation.fields.total'), 0, Alignment::HORIZONTAL_RIGHT);
        $this->fillBackground($row);
        ++$row;

        /** @var CalculationGroup $group */
        foreach ($calculation->getGroups() as $group) {
            $this->renderText(1, $row, $group->getCode());
            $this->renderAmount(2, $row, $group->getAmount());
            $this->renderPercent(3, $row, $group->getMargin());
            $this->renderAmount(4, $row, $group->getMarginAmount());
            $this->renderAmount(5, $row, $group->getTotal());
            ++$row;
        }

        // margins total
        $this->renderBold(1, $row, $this->trans('calculation.fields.marginTotal'));
        $this->renderAmount(2, $row, $calculation->getItemsTotal());
        $this->renderPercent(3, $row, $calculation->getGroupsMargin());
        $this->renderAmount(4, $row, $calculation->getGroupsMarginAmount());
        $this->renderAmount(5, $row, $calculation->getGroupsTotal(), true);
        $this->fillBackground($row);
        ++$row;

        // global margin
        $this->mergeCells(1, 2, $row);
        $this->renderText(1, $row, $this->trans('calculation.fields.globalMargin'));
        $this->renderPercent(3, $row, $calculation->getGlobalMargin());
        $this->mergeCells(4, 5, $row);
        $this->renderAmount(4, $row, $calculation->getGlobalMarginAmount());
        ++$row;

        if (!empty($calculation->getUserMargin())) {
            // total net
            $this->mergeCells(1, 4, $row);
            $this->renderBold(1, $row, $this->trans('calculation.fields.totalNet'));
            $this->renderAmount(5, $row, $calculation->getTotalNet(), true);
            $this->fillBackground($row);
            ++$row;

            // user margin
            $this->mergeCells(1, 2, $row);
            $this->renderText(1, $row, $this->trans('calculation.fields.userMargin'));
            $this->renderPercent(3, $row, $calculation->getUserMargin());
            $this->mergeCells(4, 5, $row);
            $this->renderAmount(4, $row, $calculation->getUserMarginAmount());
            ++$row;
        }

        // overall total
        $this->renderBold(1, $row, $this->trans('calculation.fields.overallTotal'));
        $this->renderAmount(2, $row, $calculation->getItemsTotal(), true);
        $this->renderPercent(3, $row, $calculation->getOverallMargin(), true, $this->getMarginFormat());
        $this->renderAmount(4, $row, $calculation->getOverallMarginAmount(), true);
        $this->renderAmount(5, $row, $calculation->getOverallTotal(), true);
        $this->fillBackground($row);

        return $this->renderEnd($row, $spaces);
    }

    /**
     * Fill the background of the given row.
     *
     * @param int $row the row to fill (1 = First row)
     */
    private function fillBackground(int $row): void
    {
        $coordinate = "A$row:E$row";
        $this->sheet->getStyle($coordinate)
            ->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB(self::COLOR_BACKGROUND);
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
    private function mergeCells(int $startColumn, int $endColumn, int $row): void
    {
        $this->sheet->mergeCellsByColumnAndRow($startColumn, $row, $endColumn, $row);
    }

    /**
     * Set a cell value with the amount format.
     *
     * @param int   $column the column index (A = 1)
     * @param int   $row    the row index (1 = First row)
     * @param mixed $value  the value of the cell
     * @param bool  $bold   true to set the bold font
     */
    private function renderAmount(int $column, int $row, $value, bool $bold = false): void
    {
        $this->renderCell($column, $row, $value, $bold, 0,
              Alignment::HORIZONTAL_RIGHT, NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
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
    private function renderBold(int $column, int $row, $value, int $indent = 0, string $alignment = '', string $format = ''): void
    {
        $this->renderCell($column, $row, $value, true, $indent, $alignment, $format);
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
    private function renderCell(int $column, int $row, $value, bool $bold = false, int $indent = 0, string $alignment = '', string $format = ''): void
    {
        $coordinate = $this->stringFromColumnAndRowIndex($column, $row);
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

    private function renderEnd(int $row, array $spaces): bool
    {
        // borders
        $this->sheet->getStyle("A1:E$row")
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB(self::COLOR_BORDER);

        foreach ($spaces as $space) {
            $this->sheet->getStyle("A$space")->getBorders()
                ->getLeft()->setBorderStyle(Border::BORDER_NONE);
            $this->sheet->getStyle("E$space")->getBorders()
                ->getRight()->setBorderStyle(Border::BORDER_NONE);
        }

        // fit columns
        foreach (\range('A', 'E') as $column) {
            $this->sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // page setup
        $this->sheet->getPageSetup()
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setHorizontalCentered(true);
        $this->sheet->setPrintGridlines(false)
            ->setShowGridlines(false);

        $this->finish('A1');

        return true;
    }

    /**
     * Render the headers for items.
     *
     * @param int $row the row index (1 = First row)
     */
    private function renderHeaders(int $row): void
    {
        $this->renderBold(1, $row, $this->trans('calculationitem.fields.description'));
        $this->renderBold(2, $row, $this->trans('calculationitem.fields.unit'));
        $this->renderBold(3, $row, $this->trans('calculationitem.fields.price'), 0, Alignment::HORIZONTAL_RIGHT);
        $this->renderBold(4, $row, $this->trans('calculationitem.fields.quantity'), 0, Alignment::HORIZONTAL_RIGHT);
        $this->renderBold(5, $row, $this->trans('calculationitem.fields.total'), 0, Alignment::HORIZONTAL_RIGHT);
        $this->fillBackground($row);
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
    private function renderPercent(int $column, int $row, float $value, bool $bold = false, string $format = NumberFormat::FORMAT_PERCENTAGE): void
    {
        $this->renderCell($column, $row, $value, $bold, 0,
            Alignment::HORIZONTAL_RIGHT, $format);
    }

    /**
     * Set a cell value .
     *
     * @param int     $column the column index (A = 1)
     * @param int     $row    the row index (1 = First row)
     * @param ?string $value  the value of the cell
     * @param int     $indent the horizontal indent
     */
    private function renderText(int $column, int $row, ?string $value, int $indent = 0): void
    {
        $this->renderCell($column, $row, $value, false, $indent);
    }
}
