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

use App\Entity\Task;
use App\Entity\TaskItem;
use App\Entity\TaskItemMargin;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroup;
use App\Pdf\PdfGroupListenerInterface;
use App\Pdf\PdfGroupTableBuilder;
use App\Pdf\PdfStyle;
use App\Util\FormatUtils;

/**
 * Report for the list of tasks.
 *
 * @author Laurent Muller
 */
class TasksReport extends AbstractArrayReport implements PdfGroupListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function onOutputGroup(PdfGroupTableBuilder $parent, PdfGroup $group): bool
    {
        /** @var Task $task */
        $task = $group->getKey();
        $parent->startRow()
            ->add($task->getName(), 1, $group->getStyle())
            ->add($task->getCategoryCode())
            ->completeRow();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        // title
        $this->setTitleTrans('task.list.title', [], true);

        // new page
        $this->AddPage();

        // table
        $table = $this->createTable();

        // styles
        $table->getGroupStyle()->setFontBold();
        $itemStyle = PdfStyle::getCellStyle()
            ->setIndent(4);
        $marginStyle = PdfStyle::getCellStyle()
            ->setIndent(8);

        /** @var Task $entity */
        foreach ($entities as $entity) {
            //check new page
            $count = 2;
            if (!$entity->isEmpty()) {
                $item = $entity->getItems()->first();
                if ($item->isEmpty()) {
                    ++$count;
                } else {
                    $count += $item->count();
                }
            }
            $height = $count * self::LINE_HEIGHT;
            if ($this->isPrintable($height)) {
                $table->setGroupKey($entity);
            } else {
                $table->setGroupKey($entity, false);
                $table->checkNewPage($count * self::LINE_HEIGHT);
            }

            $this->outputItems($table, $entity, $itemStyle, $marginStyle);
        }

        return true;
    }

    /**
     * Creates the table builder.
     */
    private function createTable(): PdfGroupTableBuilder
    {
        $table = new PdfGroupTableBuilder($this);
        $table->addColumn(PdfColumn::left($this->trans('task.fields.name'), 40))
            ->addColumn(PdfColumn::left($this->trans('task.fields.category'), 35, true))
            ->addColumn(PdfColumn::right($this->trans('taskitemmargin.fields.minimum'), 20, true))
            ->addColumn(PdfColumn::right($this->trans('taskitemmargin.fields.maximum'), 20, true))
            ->addColumn(PdfColumn::right($this->trans('taskitemmargin.fields.value'), 25, true))
            ->outputHeaders();
        $table->setGroupListener($this);

        return $table;
    }

    private function outputItem(PdfGroupTableBuilder $table, TaskItem $item, PdfStyle $itemStyle, PdfStyle $marginStyle): void
    {
        //check new page
        $count = 1 + ($item->isEmpty() ? 1 : $item->count());
        $table->checkNewPage($count * self::LINE_HEIGHT);

        $table->startRow()
            ->add($item->getName(), 1, $itemStyle)
            ->completeRow();
        $this->outputMargins($table, $item, $marginStyle);
    }

    private function outputItems(PdfGroupTableBuilder $table, Task $task, PdfStyle $itemStyle, PdfStyle $marginStyle): void
    {
        if ($task->isEmpty()) {
            $table->startRow()
                ->add($this->trans('task.edit.empty_items'), 1, $itemStyle)
                ->completeRow();
        } else {
            foreach ($task->getItems() as $item) {
                $this->outputItem($table, $item, $itemStyle, $marginStyle);
            }
        }
    }

    private function outputMargin(PdfGroupTableBuilder $table, TaskItemMargin $margin): void
    {
        $table->startRow()
            ->add('')
            ->add('')
            ->add(FormatUtils::formatAmount($margin->getMinimum()))
            ->add(FormatUtils::formatAmount($margin->getMaximum()))
            ->add(FormatUtils::formatAmount($margin->getValue()))
            ->completeRow();
    }

    private function outputMargins(PdfGroupTableBuilder $table, TaskItem $item, PdfStyle $style): void
    {
        if ($item->isEmpty()) {
            $table->startRow()
                ->add($this->trans('taskitem.edit.empty_items'), 1, $style)
                ->completeRow();
        } else {
            foreach ($item->getMargins() as $margin) {
                $this->outputMargin($table, $margin);
            }
        }
    }
}
