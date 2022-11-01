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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

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
     * Constructor.
     *
     * @param AbstractController $controller  the parent controller
     * @param Calculation        $calculation the calculation to render
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function __construct(AbstractController $controller, private readonly Calculation $calculation)
    {
        parent::__construct($controller);
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function render(): bool
    {
        $emptyRows = [];
        $calculation = $this->calculation;

        // title
        $id = $calculation->getFormattedId();
        $title = $this->trans('calculation.edit.title', ['%id%' => $id]);
        $this->start($title);

        // title
        $row = 1;
        $this->renderTitle($title, $calculation, $row);

        // space
        $this->mergeCells(1, 5, $row);
        $emptyRows[] = $row;
        ++$row;

        // empty?
        if ($calculation->isEmpty()) {
            $this->mergeCells(1, 5, $row)
                ->cellText(1, $row, $this->trans('calculation.edit.empty'))
                ->fillBackground($row);

            return $this->renderEnd($row, $emptyRows);
        }

        // groups, categories and items
        $this->renderItems($calculation, $row);

        // space
        $this->mergeCells(1, 5, $row);
        $emptyRows[] = $row;
        ++$row;

        // total by groups
        $this->renderTotalGroups($calculation, $row);

        // margins total
        $this->renderMarginsTotal($calculation, $row);

        // global margin
        $this->renderGlobalMargin($calculation, $row);

        // user margin
        $this->renderUserMargin($calculation, $row);

        // overall total
        $this->renderOverallTotal($calculation, $row);

        return $this->renderEnd($row, $emptyRows);
    }

    private function cell(int $column, int $row, mixed $value, bool $bold = false, int $indent = 0, string $alignment = '', string $format = ''): self
    {
        $sheet = $this->getActiveSheet();
        $style = $sheet->getStyleByColumnAndRow($column, $row);
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
        $this->setCellValue($sheet, $column, $row, $value);

        return $this;
    }

    private function cellAmount(int $column, int $row, float $value, bool $bold = false): self
    {
        return $this->cell(
            $column,
            $row,
            $value,
            $bold,
            0,
            Alignment::HORIZONTAL_RIGHT,
            NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
        );
    }

    private function cellBold(int $column, int $row, mixed $value, int $indent = 0, string $alignment = '', string $format = ''): self
    {
        return $this->cell($column, $row, $value, true, $indent, $alignment, $format);
    }

    private function cellPercent(int $row, float $value, bool $bold = false, string $format = NumberFormat::FORMAT_PERCENTAGE): self
    {
        return $this->cell(3, $row, $value, $bold, 0, Alignment::HORIZONTAL_RIGHT, $format);
    }

    private function cellText(int $column, int $row, ?string $value, int $indent = 0): self
    {
        return $this->cell($column, $row, $value, false, $indent);
    }

    private function fillBackground(int $row): void
    {
        $coordinate = "A$row:E$row";
        $this->getActiveSheet()->getStyle($coordinate)
            ->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB(self::COLOR_BACKGROUND);
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    private function getMarginFormat(): string
    {
        $minMargin = $this->controller->getApplication()->getMinMargin();
        $format = NumberFormat::FORMAT_PERCENTAGE;

        return "[Red][<$minMargin]$format;$format";
    }

    /**
     * @param int[] $emptyRows the empty rows indexes
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception if an exception occurs
     */
    private function renderEnd(int $lastRow, array $emptyRows): bool
    {
        // set borders to all cells
        $sheet = $this->getActiveSheet();
        $sheet->getStyle("A1:E$lastRow")
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB(self::COLOR_BORDER);

        // remove left and right borders for empty rows
        foreach ($emptyRows as $emptyRow) {
            $sheet->getStyle("A$emptyRow")->getBorders()
                ->getLeft()->setBorderStyle(Border::BORDER_NONE);
            $sheet->getStyle("E$emptyRow")->getBorders()
                ->getRight()->setBorderStyle(Border::BORDER_NONE);
        }

        // column's width
        $sheet->getColumnDimension('A')->setWidth(8.5, 'cm');
        foreach (\range('B', 'E') as $column) {
            $sheet->getColumnDimension($column)->setWidth(2.0, 'cm');
        }

        // page setup
        $sheet->getPageSetup()
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setHorizontalCentered(true)
            ->setRowsToRepeatAtTopByStartAndEnd(1, 4);

        $sheet->setPrintGridlines(false)
            ->setShowGridlines(false);

        $this->finish('A1');

        return true;
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function renderGlobalMargin(Calculation $calculation, int &$row): void
    {
        $this->mergeCells(1, 2, $row)
            ->cellText(1, $row, $this->trans('calculation.fields.globalMargin'))
            ->cellPercent($row, $calculation->getGlobalMargin())
            ->mergeCells(4, 5, $row)
            ->cellAmount(4, $row, $calculation->getGlobalMarginAmount());
        ++$row;
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function renderItems(Calculation $calculation, int &$row): void
    {
        // headers
        $this->cellBold(1, $row, $this->trans('calculationitem.fields.description'))
            ->cellBold(2, $row, $this->trans('calculationitem.fields.unit'))
            ->cellBold(3, $row, $this->trans('calculationitem.fields.price'), 0, Alignment::HORIZONTAL_RIGHT)
            ->cellBold(4, $row, $this->trans('calculationitem.fields.quantity'), 0, Alignment::HORIZONTAL_RIGHT)
            ->cellBold(5, $row, $this->trans('calculationitem.fields.total'), 0, Alignment::HORIZONTAL_RIGHT)
            ->fillBackground($row);
        ++$row;

        // groups, categories and items
        foreach ($calculation->getGroups() as $group) {
            $this->mergeCells(1, 5, $row)
                ->cellBold(1, $row, $group->getCode());
            ++$row;

            foreach ($group->getCategories() as $category) {
                $this->mergeCells(1, 5, $row)
                    ->cellBold(1, $row, $category->getCode(), 1);
                ++$row;

                foreach ($category->getItems() as $item) {
                    $this->cellText(1, $row, $item->getDescription(), 1)
                        ->cellText(2, $row, $item->getUnit())
                        ->cellAmount(3, $row, $item->getPrice())
                        ->cellAmount(4, $row, $item->getQuantity())
                        ->cellAmount(5, $row, $item->getTotal());
                    ++$row;
                }
            }
        }

        // total
        $this->mergeCells(1, 4, $row)
            ->cellBold(1, $row, $this->trans('calculation.fields.itemsTotal'))
            ->cellAmount(5, $row, $calculation->getItemsTotal(), true)
            ->fillBackground($row);
        ++$row;
    }

    private function renderMarginsTotal(Calculation $calculation, int &$row): void
    {
        $this->cellBold(1, $row, $this->trans('calculation.fields.marginTotal'))
            ->cellAmount(2, $row, $calculation->getItemsTotal())
            ->cellPercent($row, $calculation->getGroupsMargin())
            ->cellAmount(4, $row, $calculation->getGroupsMarginAmount())
            ->cellAmount(5, $row, $calculation->getGroupsTotal(), true)
            ->fillBackground($row);
        ++$row;
    }

    /**
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    private function renderOverallTotal(Calculation $calculation, int $row): void
    {
        $this->cellBold(1, $row, $this->trans('calculation.fields.overallTotal'))
            ->cellAmount(2, $row, $calculation->getItemsTotal(), true)
            ->cellPercent($row, $calculation->getOverallMargin(), true, $this->getMarginFormat())
            ->cellAmount(4, $row, $calculation->getOverallMarginAmount(), true)
            ->cellAmount(5, $row, $calculation->getOverallTotal(), true)
            ->fillBackground($row);
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function renderTitle(string $title, Calculation $calculation, int &$row): void
    {
        // title
        $this->mergeCells(1, 5, $row)
            ->cellBold(1, $row, $title, 0, Alignment::HORIZONTAL_CENTER)
            ->fillBackground($row);
        ++$row;

        // customer and state
        $this->mergeCells(1, 3, $row)
            ->cellBold(1, $row, $calculation->getCustomer())
            ->mergeCells(4, 5, $row)
            ->cellBold(4, $row, $calculation->getStateCode(), 0, Alignment::HORIZONTAL_RIGHT)
            ->fillBackground($row);
        ++$row;

        // description and date
        $this->mergeCells(1, 3, $row)
            ->cellBold(1, $row, $calculation->getDescription())
            ->mergeCells(4, 5, $row)
            ->cellBold(4, $row, $calculation->getDate(), 0, Alignment::HORIZONTAL_RIGHT, NumberFormat::FORMAT_DATE_DDMMYYYY)
            ->fillBackground($row);
        ++$row;
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function renderTotalGroups(Calculation $calculation, int &$row): void
    {
        $this->cellBold(1, $row, $this->trans('calculation.edit.panel_resume'))
            ->cellBold(2, $row, $this->trans('calculationgroup.fields.amount'), 0, Alignment::HORIZONTAL_RIGHT)
            ->mergeCells(3, 4, $row)
            ->cellBold(3, $row, $this->trans('group.fields.margins'), 0, Alignment::HORIZONTAL_CENTER)
            ->cellBold(5, $row, $this->trans('calculation.fields.total'), 0, Alignment::HORIZONTAL_RIGHT)
            ->fillBackground($row);
        ++$row;
        foreach ($calculation->getGroups() as $group) {
            $this->cellText(1, $row, $group->getCode())
                ->cellAmount(2, $row, $group->getAmount())
                ->cellPercent($row, $group->getMargin())
                ->cellAmount(4, $row, $group->getMarginAmount())
                ->cellAmount(5, $row, $group->getTotal());
            ++$row;
        }
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function renderUserMargin(Calculation $calculation, int &$row): void
    {
        if (!empty($calculation->getUserMargin())) {
            // total net
            $this->mergeCells(1, 4, $row)
                ->cellBold(1, $row, $this->trans('calculation.fields.totalNet'))
                ->cellAmount(5, $row, $calculation->getTotalNet(), true)
                ->fillBackground($row);
            ++$row;

            // user margin
            $this->mergeCells(1, 2, $row)
                ->cellText(1, $row, $this->trans('calculation.fields.userMargin'))
                ->cellPercent($row, $calculation->getUserMargin())
                ->mergeCells(4, 5, $row)
                ->cellAmount(4, $row, $calculation->getUserMarginAmount());
            ++$row;
        }
    }
}
