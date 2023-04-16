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
use App\Traits\CalculationDocumentMarginTrait;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Style;

/**
 * Spreadsheet document for a calculation.
 */
class CalculationDocument extends AbstractDocument
{
    use CalculationDocumentMarginTrait;

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
        $id = $calculation->getFormattedId();
        $title = $this->trans('calculation.edit.title', ['%id%' => $id]);
        $this->start($title);
        $row = 1;
        $this->renderTitle($title, $calculation, $row);
        $this->mergeCells(1, 5, $row);
        $emptyRows[] = $row++;
        if ($calculation->isEmpty()) {
            return $this->renderEmpty($calculation, $row, $emptyRows);
        }
        $this->renderItems($calculation, $row);
        $this->mergeCells(1, 5, $row);
        $emptyRows[] = $row;
        ++$row;
        $this->renderTotalGroups($calculation, $row);
        $this->renderMarginsTotal($calculation, $row);
        $this->renderGlobalMargin($calculation, $row);
        $this->renderUserMargin($calculation, $row);
        $this->renderOverallTotal($calculation, $row);

        return $this->renderEnd($calculation, $row, $emptyRows);
    }

    private function cell(int $column, int $row, mixed $value, bool $bold = false, int $indent = 0, string $alignment = '', string $format = ''): self
    {
        $sheet = $this->getActiveSheet();
        $style = $sheet->getCell([$column, $row])->getStyle();
        if ($bold) {
            $style->getFont()->setBold(true);
        }
        if ($indent > 0) {
            $style->getAlignment()->setIndent($indent);
        }
        if ('' !== $alignment) {
            $style->getAlignment()->setHorizontal($alignment);
        }
        if ('' !== $format) {
            $style->getNumberFormat()->setFormatCode($format);
        }
        $this->setCellValue($sheet, $column, $row, $value);

        return $this;
    }

    private function cellAmount(int $column, int $row, float $value, bool $bold = false): self
    {
        return $this->cell(
            column: $column,
            row: $row,
            value: $value,
            bold: $bold,
            alignment: Alignment::HORIZONTAL_RIGHT,
            format: NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
        );
    }

    private function cellBold(int $column, int $row, mixed $value, int $indent = 0, string $alignment = '', string $format = ''): self
    {
        return $this->cell(
            column: $column,
            row: $row,
            value: $value,
            bold: true,
            indent: $indent,
            alignment: $alignment,
            format: $format
        );
    }

    private function cellPercent(int $row, float $value, bool $bold = false, string $format = NumberFormat::FORMAT_PERCENTAGE): self
    {
        return $this->cell(
            column: 3,
            row: $row,
            value: $value,
            bold: $bold,
            alignment: Alignment::HORIZONTAL_RIGHT,
            format: $format
        );
    }

    private function cellText(int $column, int $row, ?string $value, int $indent = 0): self
    {
        return $this->cell(
            column: $column,
            row: $row,
            value: $value,
            indent: $indent
        );
    }

    private function fillBackground(int $row): void
    {
        $this->getRowStyle($row)
            ->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB(self::COLOR_BACKGROUND);
    }

    private function getRowStyle(int $row): Style
    {
        return $this->getActiveSheet()->getStyle("A$row:E$row");
    }

    /**
     * @param int[] $emptyRows the empty rows indexes
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function renderEmpty(Calculation $calculation, int $row, array $emptyRows): bool
    {
        $this->mergeCells(1, 5, $row)
            ->cellBold(
                column: 1,
                row: $row,
                value: $this->trans('calculation.edit.empty'),
                alignment: Alignment::HORIZONTAL_CENTER
            )
            ->fillBackground($row);

        return $this->renderEnd($calculation, $row, $emptyRows);
    }

    /**
     * @param int[] $emptyRows the empty rows indexes
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception if an exception occurs
     */
    private function renderEnd(Calculation $calculation, int $lastRow, array $emptyRows): bool
    {
        $sheet = $this->getActiveSheet();
        $sheet->getStyle("A1:E$lastRow")
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB(self::COLOR_BORDER);
        foreach ($emptyRows as $emptyRow) {
            $sheet->getStyle("A$emptyRow")->getBorders()
                ->getLeft()->setBorderStyle(Border::BORDER_NONE);
            $sheet->getStyle("E$emptyRow")->getBorders()
                ->getRight()->setBorderStyle(Border::BORDER_NONE);
        }
        $sheet->getColumnDimension('A')->setWidth(8.5, 'cm');
        foreach (\range('B', 'E') as $column) {
            $sheet->getColumnDimension($column)->setWidth(2.0, 'cm');
        }
        $this->renderTimestampable($calculation, $lastRow + 1);

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
        $this->cellBold(1, $row, $this->trans('calculationitem.fields.description'))
            ->cellBold(2, $row, $this->trans('calculationitem.fields.unit'))
            ->cellBold(3, $row, $this->trans('calculationitem.fields.price'), 0, Alignment::HORIZONTAL_RIGHT)
            ->cellBold(4, $row, $this->trans('calculationitem.fields.quantity'), 0, Alignment::HORIZONTAL_RIGHT)
            ->cellBold(5, $row, $this->trans('calculationitem.fields.total'), 0, Alignment::HORIZONTAL_RIGHT)
            ->fillBackground($row);
        ++$row;
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
    private function renderTimestampable(Calculation $calculation, int $row): void
    {
        $translator = $this->getTranslator();
        $created = $calculation->getCreatedText($translator);
        $updated = $calculation->getUpdatedText($translator);
        $this->cell(1, $row, $created);
        $this->mergeCells(2, 5, $row)
            ->cell(
                column: 2,
                row: $row,
                value: $updated,
                alignment: Alignment::HORIZONTAL_RIGHT
            );
        $this->getRowStyle($row)
            ->getFont()
            ->setSize(9)
            ->setItalic(true);
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function renderTitle(string $title, Calculation $calculation, int &$row): void
    {
        $this->mergeCells(1, 5, $row)
            ->cellBold(1, $row, $title, 0, Alignment::HORIZONTAL_CENTER)
            ->fillBackground($row);
        ++$row;
        $this->mergeCells(1, 3, $row)
            ->cellBold(1, $row, $calculation->getCustomer())
            ->mergeCells(4, 5, $row)
            ->cellBold(4, $row, $calculation->getStateCode(), 0, Alignment::HORIZONTAL_RIGHT)
            ->fillBackground($row);
        ++$row;
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
            $this->mergeCells(1, 4, $row)
                ->cellBold(1, $row, $this->trans('calculation.fields.totalNet'))
                ->cellAmount(5, $row, $calculation->getTotalNet(), true)
                ->fillBackground($row);
            ++$row;
            $this->mergeCells(1, 2, $row)
                ->cellText(1, $row, $this->trans('calculation.fields.userMargin'))
                ->cellPercent($row, $calculation->getUserMargin())
                ->mergeCells(4, 5, $row)
                ->cellAmount(4, $row, $calculation->getUserMarginAmount());
            ++$row;
        }
    }
}
