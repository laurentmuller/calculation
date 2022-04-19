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

use App\Entity\Group;
use App\Pdf\PdfBorder;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Util\FormatUtils;

/**
 * Report for the list of groups.
 *
 * @author Laurent Muller
 *
 * @extends AbstractArrayReport<Group>
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
        $emptyStyle = PdfStyle::getCellStyle()->setBorder(PdfBorder::LEFT . PdfBorder::RIGHT);

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

        $margins = $this->setCellMargin(0);
        $table = new PdfTableBuilder($this);
        $table->addColumn(PdfColumn::left(null, 20))
            ->addColumn(PdfColumn::center(null, 20))
            ->addColumn(PdfColumn::right(null, 20))
            ->startRow(PdfStyle::getNoBorderStyle())
            ->add($txtCount)
            ->add($txtCategory)
            ->add($txtMargin)
            ->endRow();
        $this->setCellMargin($margins);

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
            ->addColumn(PdfColumn::right($this->trans('groupmargin.fields.minimum'), 22, true))
            ->addColumn(PdfColumn::right($this->trans('groupmargin.fields.maximum'), 22, true))
            ->addColumn(PdfColumn::right($this->trans('groupmargin.fields.margin'), 18, true))
            ->outputHeaders();

        return $table;
    }

    /**
     * Ouput a group.
     *
     * @param PdfTableBuilder $table the table to render to
     * @param Group           $group the group to output
     */
    private function outputGroup(PdfTableBuilder $table, Group $group): void
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
                    ->add(FormatUtils::formatPercent($margin->getMargin()))
                    ->endRow();
                $skip = true;
            }
        } else {
            $empty = $this->trans('group.edit.empty_margins');
            $table->add($empty, 3)->endRow();
        }
    }
}
