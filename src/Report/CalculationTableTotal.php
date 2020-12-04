<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Report;

use App\Entity\Calculation;
use App\Entity\CalculationGroup;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Util\FormatUtils;

/**
 * Table to render a list of calculation totals.
 *
 * @author Laurent Muller
 */
class CalculationTableTotal extends PdfTableBuilder
{
    /**
     * Constructor.
     *
     * @param CalculationReport $parent the parent document to print in
     */
    public function __construct(CalculationReport $parent)
    {
        parent::__construct($parent, true);
    }

    /**
     * Output the given calculation.
     *
     * @param Calculation $calculation the calculation to output
     */
    public function output(Calculation $calculation): void
    {
        /** @var \Doctrine\Common\Collections\Collection|CalculationGroup[] $groups */
        $groups = $calculation->getRootGroups();
        if ($groups->isEmpty()) {
            return;
        }

        /** @var CalculationReport $parent */
        $parent = $this->parent;
        $style = PdfStyle::getHeaderStyle()->setFontRegular();

        // header
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
            ->add($this->trans('report.calculation.margins'), 2, null, self::ALIGN_CENTER)
            ->add($columns[4]->getText())
            ->endRow();

        // groups
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
     * Render the table for the given groups.
     *
     * @param CalculationReport $parent the parent document to print in
     */
    public static function render(CalculationReport $parent): void
    {
        $table = new self($parent);
        $table->output($parent->getCalculation());
    }

    /**
     * Translates the given message.
     *
     * @param string $id the message id (may also be an object that can be cast to string)
     *
     * @return string the translated string
     *
     * @throws \InvalidArgumentException if the locale contains invalid characters
     */
    private function trans($id): string
    {
        /** @var AbstractReport $parent */
        $parent = $this->parent;

        return $parent->trans($id);
    }
}
