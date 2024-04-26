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

namespace App\Report\Table;

use App\Entity\Calculation;
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Report\CalculationReport;
use fpdf\PdfBorder;

/**
 * Table to render the overall totals of a calculation.
 */
class TableOverall extends ReportTable
{
    private readonly Calculation $calculation;
    private readonly float $minMargin;

    public function __construct(CalculationReport $parent)
    {
        parent::__construct($parent, $parent->getTranslator());
        $this->calculation = $parent->getCalculation();
        $this->minMargin = $parent->getMinMargin();
    }

    /**
     * Output overall totals.
     */
    public function output(): void
    {
        $calculation = $this->calculation;
        $totalItems = $calculation->getGroupsAmount();
        $totalMargins = $calculation->getGroupsMarginAmount();
        $totalBrut = $totalItems + $totalMargins;
        $globalMargin = $calculation->getGlobalMargin();
        $globalAmount = $totalBrut * ($globalMargin - 1.0);
        $totalNet = $totalBrut + $globalAmount;
        $userMargin = $calculation->getUserMargin();
        $userAmount = $totalNet * $userMargin;

        $this->createColumns()
            ->outputGlobalMargin($globalMargin, $globalAmount)
            ->outputUserMargin($userMargin, $userAmount, $totalNet)
            ->outputOverallTotal($calculation, $totalItems);
    }

    /**
     * Render the table for the given report.
     */
    public static function render(CalculationReport $parent): self
    {
        $table = new self($parent);
        $table->output();

        return $table;
    }

    public function startRow(?PdfStyle $style = null): static
    {
        if (!$style instanceof PdfStyle) {
            $style = PdfStyle::getCellStyle()
                ->setBorder(PdfBorder::leftRight());
        }
        parent::startRow($style);

        return $this;
    }

    private function createColumns(): self
    {
        return $this->addColumns(
            PdfColumn::left(null, 50),
            PdfColumn::right(null, 20, true),
            PdfColumn::right(null, 20, true),
            PdfColumn::right(null, 20, true),
            PdfColumn::right(null, 20, true)
        )->setRepeatHeader(false);
    }

    private function outputGlobalMargin(float $globalMargin, float $globalAmount): self
    {
        return $this->startRow()
            ->addCellTrans('calculation.fields.globalMargin')
            ->add()
            ->addCellPercent($globalMargin)
            ->add()
            ->addCellAmount($globalAmount)
            ->endRow();
    }

    private function outputOverallTotal(Calculation $calculation, float $totalItems): self
    {
        $style = null;
        if ($calculation->isMarginBelow($this->minMargin)) {
            $style = PdfStyle::getHeaderStyle()->setTextColor(PdfTextColor::red());
        }
        $this->startHeaderRow()
            ->addCellTrans('calculation.fields.overallTotal')
            ->addCellAmount($totalItems)
            ->addCellPercent($calculation->getOverallMargin(), style: $style)
            ->addCellAmount($calculation->getOverallMarginAmount())
            ->addCellAmount($calculation->getOverallTotal())
            ->endRow();

        return $this;
    }

    private function outputUserMargin(float $userMargin, float $userAmount, float $totalNet): self
    {
        if (0.0 !== $userMargin) {
            $this->startHeaderRow()
                ->addCellTrans('calculation.fields.totalNet', cols: 4)
                ->addCellAmount($totalNet)
                ->endRow();
            $this->startRow()
                ->addCellTrans('calculation.fields.userMargin')
                ->add()
                ->addCellPercent($userMargin)
                ->add()
                ->addCellAmount($userAmount)
                ->endRow();
        }

        return $this;
    }
}
