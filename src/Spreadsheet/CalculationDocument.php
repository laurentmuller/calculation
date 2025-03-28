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
use App\Traits\MathTrait;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Spreadsheet document for a calculation.
 */
class CalculationDocument extends AbstractDocument
{
    use CalculationDocumentMarginTrait;
    use MathTrait;

    /**
     * The header background color.
     */
    private const COLOR_BACKGROUND = 'F5F5F5';

    /**
     * The cell border color.
     */
    private const COLOR_BORDER = 'DDDDDD';

    /**
     * @param AbstractController $controller  the parent controller
     * @param Calculation        $calculation the calculation to render
     */
    public function __construct(AbstractController $controller, private readonly Calculation $calculation)
    {
        parent::__construct($controller);
    }

    #[\Override]
    public function render(): bool
    {
        $calculation = $this->calculation;
        $id = $calculation->getFormattedId();
        $title = $this->trans('calculation.edit.title', ['%id%' => $id]);
        $this->start($title);

        $row = 1;
        $sheet = $this->getActiveSheet();
        $this->renderTitle($sheet, $title, $calculation, $row);
        $sheet->mergeContent(1, 5, $row);
        ++$row;

        if ($calculation->isEmpty()) {
            return $this->renderEmpty($sheet, $calculation, $row);
        }

        $this->renderItems($sheet, $calculation, $row);
        ++$row;

        $this->renderTotalGroups($sheet, $calculation, $row);
        $this->renderMarginsTotal($sheet, $calculation, $row);
        $this->renderGlobalMargin($sheet, $calculation, $row);
        $this->renderUserMargin($sheet, $calculation, $row);
        $this->renderOverallTotal($sheet, $calculation, $row);

        return $this->renderEnd($sheet, $calculation, $row);
    }

    private function applyBorderStyle(Border $border): self
    {
        $border->setBorderStyle(Border::BORDER_THIN)
            ->getColor()
            ->setARGB(self::COLOR_BORDER);

        return $this;
    }

    private function cell(
        WorksheetDocument $sheet,
        int $column,
        int $row,
        mixed $value,
        bool $bold = false,
        int $indent = 0,
        string $alignment = '',
        string $format = ''
    ): self {
        $style = $sheet->getStyle([$column, $row]);
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

        $sheet->setCellContent($column, $row, $value);

        return $this;
    }

    private function cellAmount(WorksheetDocument $sheet, int $column, int $row, float $value, bool $bold = false): self
    {
        return $this->cell(
            sheet: $sheet,
            column: $column,
            row: $row,
            value: $value,
            bold: $bold,
            alignment: Alignment::HORIZONTAL_RIGHT,
            format: NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1
        );
    }

    private function cellBold(WorksheetDocument $sheet, int $column, int $row, mixed $value, int $indent = 0, string $alignment = '', string $format = ''): self
    {
        return $this->cell(
            sheet: $sheet,
            column: $column,
            row: $row,
            value: $value,
            bold: true,
            indent: $indent,
            alignment: $alignment,
            format: $format
        );
    }

    private function cellPercent(WorksheetDocument $sheet, int $row, float $value, bool $bold = false, string $format = NumberFormat::FORMAT_PERCENTAGE): self
    {
        return $this->cell(
            sheet: $sheet,
            column: 3,
            row: $row,
            value: $value,
            bold: $bold,
            alignment: Alignment::HORIZONTAL_RIGHT,
            format: $format
        );
    }

    private function cellText(WorksheetDocument $sheet, int $column, int $row, ?string $value, int $indent = 0): self
    {
        return $this->cell(
            sheet: $sheet,
            column: $column,
            row: $row,
            value: $value,
            indent: $indent
        );
    }

    private function fillBackground(WorksheetDocument $sheet, int $row): self
    {
        $sheet->getStyle("A$row:E$row")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB(self::COLOR_BACKGROUND);

        return $this;
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function renderEmpty(WorksheetDocument $sheet, Calculation $calculation, int $row): bool
    {
        $sheet->mergeContent(1, 5, $row);
        $this->cellBold(
            sheet: $sheet,
            column: 1,
            row: $row,
            value: $this->trans('calculation.edit.empty'),
            alignment: Alignment::HORIZONTAL_CENTER
        );
        $this->fillBackground($sheet, $row)
            ->setAllBorders($sheet, $row);

        return $this->renderEnd($sheet, $calculation, $row);
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function renderEnd(WorksheetDocument $sheet, Calculation $calculation, int $lastRow): bool
    {
        $sheet->getColumnDimensionByColumn(1)->setWidth(10.5, 'cm');
        foreach (\range(2, 5) as $column) {
            $sheet->getColumnDimensionByColumn($column)->setWidth(2.0, 'cm');
        }
        $this->renderTimestampable($sheet, $calculation, $lastRow + 1);

        $sheet->getPageSetup()
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setHorizontalCentered(true)
            ->setRowsToRepeatAtTopByStartAndEnd(1, 4);

        $sheet->setPrintGridlines(false)
            ->setShowGridlines(false)
            ->finish('A1');

        return true;
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function renderGlobalMargin(WorksheetDocument $sheet, Calculation $calculation, int &$row): void
    {
        $this->cellText($sheet, 1, $row, $this->trans('calculation.fields.globalMargin'))
            ->cellPercent($sheet, $row, $calculation->getGlobalMargin())
            ->setLeftRightBorders($sheet, $row);
        $this->cellAmount($sheet, 5, $row, $calculation->getGlobalMarginAmount())
            ->setLeftRightBorders($sheet, $row);
        ++$row;
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function renderItems(WorksheetDocument $sheet, Calculation $calculation, int &$row): void
    {
        $this->cellBold($sheet, 1, $row, $this->trans('calculationitem.fields.description'))
            ->cellBold($sheet, 2, $row, $this->trans('calculationitem.fields.unit'))
            ->cellBold($sheet, 3, $row, $this->trans('calculationitem.fields.price'), 0, Alignment::HORIZONTAL_RIGHT)
            ->cellBold($sheet, 4, $row, $this->trans('calculationitem.fields.quantity'), 0, Alignment::HORIZONTAL_RIGHT)
            ->cellBold($sheet, 5, $row, $this->trans('calculationitem.fields.total'), 0, Alignment::HORIZONTAL_RIGHT)
            ->fillBackground($sheet, $row);
        $this->setAllBorders($sheet, $row);
        ++$row;

        foreach ($calculation->getGroups() as $group) {
            $this->cellBold($sheet, 1, $row, $group->getCode())
                ->setLeftRightBorders($sheet, $row);
            ++$row;

            foreach ($group->getCategories() as $category) {
                $this->cellBold($sheet, 1, $row, $category->getCode(), 1)
                    ->setLeftRightBorders($sheet, $row);
                ++$row;

                foreach ($category->getItems() as $item) {
                    $this->cellText($sheet, 1, $row, $item->getDescription(), 1)
                        ->cellText($sheet, 2, $row, $item->getUnit())
                        ->cellAmount($sheet, 3, $row, $item->getPrice())
                        ->cellAmount($sheet, 4, $row, $item->getQuantity())
                        ->cellAmount($sheet, 5, $row, $item->getTotal())
                        ->setLeftRightBorders($sheet, $row);
                    ++$row;
                }
            }
        }

        $this->cellBold($sheet, 1, $row, $this->trans('calculation.fields.itemsTotal'))
            ->cellAmount($sheet, 5, $row, $calculation->getItemsTotal(), true)
            ->fillBackground($sheet, $row)
            ->setAllBorders($sheet, $row);
        ++$row;
    }

    private function renderMarginsTotal(WorksheetDocument $sheet, Calculation $calculation, int &$row): void
    {
        $this->cellBold($sheet, 1, $row, $this->trans('calculation.fields.marginTotal'))
            ->cellAmount($sheet, 2, $row, $calculation->getItemsTotal())
            ->cellPercent($sheet, $row, $calculation->getGroupsMargin())
            ->cellAmount($sheet, 4, $row, $calculation->getGroupsMarginAmount())
            ->cellAmount($sheet, 5, $row, $calculation->getGroupsTotal(), true)
            ->fillBackground($sheet, $row)
            ->setAllBorders($sheet, $row);
        ++$row;
    }

    private function renderOverallTotal(WorksheetDocument $sheet, Calculation $calculation, int $row): void
    {
        $this->cellBold($sheet, 1, $row, $this->trans('calculation.fields.overallTotal'))
            ->cellAmount($sheet, 2, $row, $calculation->getItemsTotal(), true)
            ->cellPercent($sheet, $row, $calculation->getOverallMargin(), true, $this->getMarginFormat())
            ->cellAmount($sheet, 4, $row, $calculation->getOverallMarginAmount(), true)
            ->cellAmount($sheet, 5, $row, $calculation->getOverallTotal(), true)
            ->fillBackground($sheet, $row)
            ->setAllBorders($sheet, $row);
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function renderTimestampable(WorksheetDocument $sheet, Calculation $calculation, int $row): void
    {
        $translator = $this->getTranslator();
        $created = $calculation->getCreatedMessage()->trans($translator);
        $updated = $calculation->getUpdatedMessage()->trans($translator);
        $this->cell($sheet, 1, $row, $created);
        $sheet->mergeContent(2, 5, $row);
        $this->cell(
            sheet: $sheet,
            column: 2,
            row: $row,
            value: $updated,
            alignment: Alignment::HORIZONTAL_RIGHT
        );

        $sheet->getStyle("A$row:E$row")
            ->getFont()
            ->setSize(8);
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function renderTitle(WorksheetDocument $sheet, string $title, Calculation $calculation, int &$row): void
    {
        $sheet->mergeContent(1, 5, $row);
        $this->cellBold($sheet, 1, $row, $title, 0, Alignment::HORIZONTAL_CENTER)
            ->fillBackground($sheet, $row)
            ->setAllBorders($sheet, $row);
        ++$row;

        $sheet->mergeContent(1, 3, $row);
        $this->cellBold($sheet, 1, $row, $calculation->getCustomer());
        $sheet->mergeContent(4, 5, $row);
        $this->cellBold($sheet, 4, $row, $calculation->getStateCode(), 0, Alignment::HORIZONTAL_RIGHT)
            ->fillBackground($sheet, $row)
            ->setAllBorders($sheet, $row);
        ++$row;

        $sheet->mergeContent(1, 3, $row);
        $this->cellBold($sheet, 1, $row, $calculation->getDescription());
        $sheet->mergeContent(4, 5, $row);
        $this->cellBold($sheet, 4, $row, $calculation->getDate(), 0, Alignment::HORIZONTAL_RIGHT, NumberFormat::FORMAT_DATE_DDMMYYYY)
            ->fillBackground($sheet, $row)
            ->setAllBorders($sheet, $row);
        ++$row;
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function renderTotalGroups(WorksheetDocument $sheet, Calculation $calculation, int &$row): void
    {
        $this->cellBold($sheet, 1, $row, $this->trans('calculation.edit.panel_resume'))
            ->cellBold($sheet, 2, $row, $this->trans('calculationgroup.fields.amount'), 0, Alignment::HORIZONTAL_RIGHT);
        $this->cellBold($sheet, 3, $row, $this->trans('group.fields.margins'), 0, Alignment::HORIZONTAL_CENTER)
            ->cellBold($sheet, 5, $row, $this->trans('calculation.fields.total'), 0, Alignment::HORIZONTAL_RIGHT)
            ->fillBackground($sheet, $row)
            ->setAllBorders($sheet, $row);
        ++$row;

        foreach ($calculation->getGroups() as $group) {
            $this->cellText($sheet, 1, $row, $group->getCode())
                ->cellAmount($sheet, 2, $row, $group->getAmount())
                ->cellPercent($sheet, $row, $group->getMargin())
                ->cellAmount($sheet, 4, $row, $group->getMarginAmount())
                ->cellAmount($sheet, 5, $row, $group->getTotal())
                ->setLeftRightBorders($sheet, $row);
            ++$row;
        }
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    private function renderUserMargin(WorksheetDocument $sheet, Calculation $calculation, int &$row): void
    {
        if ($this->isFloatZero($calculation->getUserMargin())) {
            return;
        }

        $this->cellBold($sheet, 1, $row, $this->trans('calculation.fields.totalNet'))
            ->cellAmount($sheet, 5, $row, $calculation->getTotalNet(), true)
            ->fillBackground($sheet, $row)
            ->setAllBorders($sheet, $row);
        ++$row;

        $this->cellText($sheet, 1, $row, $this->trans('calculation.fields.userMargin'))
            ->cellPercent($sheet, $row, $calculation->getUserMargin());
        $this->cellAmount($sheet, 5, $row, $calculation->getUserMarginAmount())
            ->setLeftRightBorders($sheet, $row);
        ++$row;
    }

    private function setAllBorders(WorksheetDocument $sheet, int $row): self
    {
        $borders = $sheet->getStyle("A$row:E$row")
            ->getBorders()
            ->getAllBorders();

        return $this->applyBorderStyle($borders);
    }

    private function setLeftRightBorders(WorksheetDocument $sheet, int $row): self
    {
        foreach (\range(1, 5) as $col) {
            $borders = $sheet->getStyle([$col, $row])
                ->getBorders();
            $this->applyBorderStyle($borders->getLeft())
                ->applyBorderStyle($borders->getRight());
        }

        return $this;
    }
}
