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

use App\Entity\Category;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Util\FormatUtils;

/**
 * Report for the list of groups.
 *
 * @author Laurent Muller
 */
class GroupsReport extends AbstractArrayReport
{
    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        // title
        $this->setTitleTrans('group.list.title', [], true);

        // count values
        $marginsCount = 0;
        $groupsCount = 0;
        foreach ($entities as $entity) {
            $marginsCount += $entity->countMargins();
            $groupsCount += $entity->countCategories();
        }

        // new page
        $this->AddPage();

        // table
        $table = $this->createTable();

        $last = \end($entities);
        $emptyStyle = PdfStyle::getCellStyle()->setBorder('LR');

        /** @var Category $entity */
        foreach ($entities as $entity) {
            $this->outputGroup($table, $entity);
            if ($entity !== $last) {
                $table->singleLine(null, $emptyStyle);
            }
        }
        $this->resetStyle();

        // totals
        $txtCount = $this->trans('counters.groups', [
            'count' => \count($entities),
        ]);
        $txtCategory = $this->trans('counters.categories', [
            'count' => $groupsCount,
        ]);
        $txtMargin = $this->trans('counters.margins', [
            'count' => $marginsCount,
        ]);

        $table = new PdfTableBuilder($this);
        $table->addColumn(PdfColumn::left(null, 20))
            ->addColumn(PdfColumn::center(null, 20))
            ->addColumn(PdfColumn::right(null, 20))
            ->startRow(PdfStyle::getNoBorderStyle())
            ->add($txtCount)
            ->add($txtCategory)
            ->add($txtMargin)
            ->endRow();

        return true;
    }

    /**
     * Creates the table builder.
     *
     * @return PdfTableBuilder the table
     */
    private function createTable(): PdfTableBuilder
    {
        $table = new PdfTableBuilder($this);
        $table->addColumn(PdfColumn::left($this->trans('group.fields.code'), 40, true))
            ->addColumn(PdfColumn::left($this->trans('group.fields.description'), 50))
            ->addColumn(PdfColumn::right($this->trans('group.fields.categories'), 25, true))
            ->addColumn(PdfColumn::right($this->trans('categorymargin.fields.minimum'), 22, true))
            ->addColumn(PdfColumn::right($this->trans('categorymargin.fields.maximum'), 22, true))
            ->addColumn(PdfColumn::right($this->trans('categorymargin.fields.margin'), 18, true))
            ->outputHeaders();

        return $table;
    }

    /**
     * Ouput a group.
     *
     * @param PdfTableBuilder $table the table to render to
     * @param Category        $group the category to output
     */
    private function outputGroup(PdfTableBuilder $table, Category $group): void
    {
        $table->startRow()
            ->add($group->getCode())
            ->add($group->getDescription())
            ->add(FormatUtils::formatInt($group->countCategories()));

        if ($group->hasMargins()) {
            $skip = false;
            $margins = $group->getMargins();
            foreach ($margins as $margin) {
                if ($skip) {
                    $table->startRow()
                        ->add('')
                        ->add('')
                        ->add('');
                }
                $table->add(FormatUtils::formatAmount($margin->getMinimum()))
                    ->add(FormatUtils::formatAmount($margin->getMaximum()))
                    ->add(FormatUtils::formatPercent($margin->getMargin(), false))
                    ->endRow();
                $skip = true;
            }
        } else {
            $empty = $this->trans('report.groups.empty_margins');
            $table->add($empty, 3)->endRow();
        }
    }
}
