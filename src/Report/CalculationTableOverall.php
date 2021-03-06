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

namespace App\Report;

use App\Entity\Calculation;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Pdf\PdfTextColor;
use App\Util\FormatUtils;

/**
 * Table to render the overall totals of a calculation.
 *
 * @author Laurent Muller
 */
class CalculationTableOverall extends PdfTableBuilder
{
    /**
     * The calculation to render.
     */
    private Calculation $calculation;

    /**
     * The minimum margin.
     */
    private float $minMargin;

    /**
     * Constructor.
     */
    public function __construct(CalculationReport $parent)
    {
        parent::__construct($parent);
        $this->calculation = $parent->getCalculation();
        $this->minMargin = $parent->getMinMargin();
    }

    /**
     * Output overall totals.
     */
    public function output(): void
    {
        $calculation = $this->calculation;

        $columns = [
            PdfColumn::left(null, 50, false),
            PdfColumn::right(null, 20, true),
            PdfColumn::right(null, 20, true),
            PdfColumn::right(null, 20, true),
            PdfColumn::right(null, 20, true),
        ];
        $this->addColumns($columns)->setRepeatHeader(false);

        // compute values
        $totalItems = $calculation->getGroupsAmount();
        $totalMargins = $calculation->getGroupsMarginAmount();
        $totalBrut = $totalItems + $totalMargins;

        $globalMargin = $calculation->getGlobalMargin();
        $globalAmount = $totalBrut * ($globalMargin - 1);

        $totalNet = $totalBrut + $globalAmount;
        $userMargin = $calculation->getUserMargin();
        $userAmount = $totalNet * $userMargin;

        // global margin
        $this->startRow()
            ->add($this->trans('calculation.fields.globalMargin'), 2)
            ->add(FormatUtils::formatPercent($globalMargin))
            ->add(FormatUtils::formatAmount($globalAmount), 2)
            ->endRow();

        // user margin
        if (!empty($userMargin)) {
            $this->startHeaderRow()
                ->add($this->trans('calculation.fields.totalNet'), 4)
                ->add(FormatUtils::formatAmount($totalNet))
                ->endRow();
            $this->startRow()
                ->add($this->trans('calculation.fields.userMargin'), 2)
                ->add(FormatUtils::formatPercent($userMargin))
                ->add(FormatUtils::formatAmount($userAmount), 2)
                ->endRow();
        }

        // style for margin
        $style = null;
        if ($calculation->isMarginBelow($this->minMargin)) {
            $style = PdfStyle::getHeaderStyle()->setTextColor(PdfTextColor::red());
        }

        // overall margin and amouts
        $this->startHeaderRow()
            ->add($this->trans('calculation.fields.overallTotal'))
            ->add(FormatUtils::formatAmount($totalItems))
            ->add(FormatUtils::formatPercent($calculation->getOverallMargin()), 1, $style)
            ->add(FormatUtils::formatAmount($calculation->getOverallMarginAmount()))
            ->add(FormatUtils::formatAmount($calculation->getOverallTotal()))
            ->endRow();
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

    private function trans(string $id): string
    {
        /** @var CalculationReport $parent */
        $parent = $this->getParent();

        return $parent->trans($id);
    }
}
