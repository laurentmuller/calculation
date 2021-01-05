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
            ->add($task->getUnit());
        if ($task->isEmpty()) {
            $parent->add($this->trans('task.edit.empty_items'), 3);
        }
        $parent->completeRow();

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

        /** @var Task $entity */
        foreach ($entities as $entity) {
            //check for new page
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

            /** @var TaskItem $item */
            foreach ($entity->getItems() as $item) {
                if ($item->isEmpty()) {
                    $table->startRow()
                        ->add($item->getName(), 1, $itemStyle)
                        ->add('')
                        ->add('')
                        ->add($this->trans('taskitem.edit.empty_items'), 3)
                        ->endRow();
                } else {
                    $index = 0;

                    /** @var TaskItemMargin $margin */
                    foreach ($item->getMargins() as $margin) {
                        $table->startRow();
                        if (0 === $index++) {
                            $table->add($item->getName(), 1, $itemStyle);
                        } else {
                            $table->add('');
                        }
                        $table->add('')
                            ->add('')
                            ->add(FormatUtils::formatAmount($margin->getMinimum()))
                            ->add(FormatUtils::formatAmount($margin->getMaximum()))
                            ->add(FormatUtils::formatAmount($margin->getValue()))
                            ->endRow();
                    }
                }
            }
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
            ->addColumn(PdfColumn::left($this->trans('task.fields.unit'), 15, true))
            ->addColumn(PdfColumn::right($this->trans('taskitemmargin.fields.minimum'), 20, true))
            ->addColumn(PdfColumn::right($this->trans('taskitemmargin.fields.maximum'), 20, true))
            ->addColumn(PdfColumn::right($this->trans('taskitemmargin.fields.value'), 20, true))
            ->outputHeaders();
        $table->setGroupListener($this);

        return $table;
    }
}
