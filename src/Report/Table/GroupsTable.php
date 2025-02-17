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
use App\Entity\CalculationGroup;
use App\Pdf\PdfStyle;
use App\Report\CalculationReport;
use fpdf\Enums\PdfTextAlignment;
use fpdf\PdfBorder;

/**
 * Table to render the totals by calculation's group.
 */
class GroupsTable extends ReportTable
{
    private readonly Calculation $calculation;

    public function __construct(CalculationReport $parent)
    {
        parent::__construct($parent);
        $this->calculation = $parent->getCalculation();
    }

    /**
     * Output totals by calculation's group.
     */
    public function output(): void
    {
        $this->createColumns();
        $calculation = $this->calculation;
        foreach ($calculation->getGroups() as $group) {
            $this->outputGroup($group);
        }
        $this->outputTotal($calculation);
    }

    /**
     * Create and render the table for the given report.
     */
    public static function render(CalculationReport $parent): void
    {
        $table = new self($parent);
        $table->output();
    }

    #[\Override]
    public function startRow(?PdfStyle $style = null): static
    {
        if (!$style instanceof PdfStyle) {
            $style = PdfStyle::getCellStyle()
                ->setBorder(PdfBorder::leftRight());
        }
        parent::startRow($style);

        return $this;
    }

    private function createColumns(): void
    {
        $columns = [
            $this->leftColumn('report.calculation.resume', 50),
            $this->rightColumn('report.calculation.amount', 20, true),
            $this->rightColumn('report.calculation.margin_percent', 20, true),
            $this->rightColumn('report.calculation.margin_amount', 20, true),
            $this->rightColumn('report.calculation.total', 20, true),
        ];
        $this->addColumns(...$columns);

        $this->startHeaderRow()
            ->add($columns[0]->getText())
            ->add($columns[1]->getText())
            ->addCellTrans('report.calculation.margins', cols: 2, alignment: PdfTextAlignment::CENTER)
            ->add($columns[4]->getText())
            ->endRow();
    }

    private function outputGroup(CalculationGroup $group): void
    {
        $this->startRow()
            ->add($group->getCode())
            ->addCellAmount($group->getAmount())
            ->addCellPercent($group->getMargin())
            ->addCellAmount($group->getMarginAmount())
            ->addCellAmount($group->getTotal())
            ->endRow();
    }

    private function outputTotal(Calculation $calculation): void
    {
        $style = PdfStyle::getHeaderStyle()->setFontRegular();
        $this->startHeaderRow()
            ->addCellTrans('calculation.fields.marginTotal')
            ->addCellAmount($calculation->getGroupsAmount(), style: $style)
            ->addCellPercent($calculation->getGroupsMargin(), style: $style)
            ->addCellAmount($calculation->getGroupsMarginAmount(), style: $style)
            ->addCellAmount($calculation->getGroupsTotal())
            ->endRow();
    }
}
