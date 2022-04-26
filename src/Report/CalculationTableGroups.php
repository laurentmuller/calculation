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

namespace App\Report;

use App\Entity\Calculation;
use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Traits\TranslatorTrait;
use App\Util\FormatUtils;

/**
 * Table to render the totals by group of a calculation.
 */
class CalculationTableGroups extends PdfTableBuilder
{
    use TranslatorTrait;

    /**
     * The calculation to render.
     */
    private readonly Calculation $calculation;

    /**
     * Constructor.
     */
    public function __construct(CalculationReport $parent)
    {
        parent::__construct($parent);
        $this->setTranslator($parent->getTranslator());
        $this->calculation = $parent->getCalculation();
    }

    /**
     * Output totals by group.
     */
    public function output(): void
    {
        $calculation = $this->calculation;
        $groups = $calculation->getGroups();
        if ($groups->isEmpty()) {
            return;
        }

        // style
        $style = PdfStyle::getHeaderStyle()->setFontRegular();

        // headers
        $columns = [
            PdfColumn::left($this->trans('report.calculation.resume'), 50),
            PdfColumn::right($this->trans('report.calculation.amount'), 20, true),
            PdfColumn::right($this->trans('report.calculation.margin_percent'), 20, true),
            PdfColumn::right($this->trans('report.calculation.margin_amount'), 20, true),
            PdfColumn::right($this->trans('report.calculation.total'), 20, true),
        ];
        $this->addColumns($columns);

        $this->startHeaderRow()
            ->add($columns[0]->getText())
            ->add($columns[1]->getText())
            ->add($this->trans('report.calculation.margins'), 2, null, PdfTextAlignment::CENTER)
            ->add($columns[4]->getText())
            ->endRow();

        // groupes
        foreach ($groups as $group) {
            $this->startRow()
                ->add($group->getCode())
                ->add(FormatUtils::formatAmount($group->getAmount()))
                ->add(FormatUtils::formatPercent($group->getMargin()))
                ->add(FormatUtils::formatAmount($group->getMarginAmount()))
                ->add(FormatUtils::formatAmount($group->getTotal()))
                ->endRow();
        }

        // groups total
        $this->startHeaderRow()
            ->add($this->trans('calculation.fields.marginTotal'))
            ->add(FormatUtils::formatAmount($calculation->getGroupsAmount()), 1, $style)
            ->add(FormatUtils::formatPercent($calculation->getGroupsMargin()), 1, $style)
            ->add(FormatUtils::formatAmount($calculation->getGroupsMarginAmount()), 1, $style)
            ->add(FormatUtils::formatAmount($calculation->getGroupsTotal()))
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
}
