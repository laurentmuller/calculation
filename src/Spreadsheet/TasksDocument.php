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

/**
 * Spreadsheet document for the list of tasks.
 *
 * @extends AbstractArrayDocument<\App\Entity\Task>
 */
class TasksDocument extends AbstractArrayDocument
{
    /**
     * @param \App\Entity\Task[] $entities
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function doRender(array $entities): bool
    {
        $this->start('task.list.title');

        $sheet = $this->getActiveSheet();
        $row = $sheet->setHeaders([
            'task.fields.name' => HeaderFormat::instance(),
            'task.fields.group' => HeaderFormat::instance(),
            'task.fields.category' => HeaderFormat::instance(),
            'task.fields.unit' => HeaderFormat::instance(),
            'task.fields.supplier' => HeaderFormat::instance(),
            'taskitemmargin.fields.minimum' => HeaderFormat::amount(),
            'taskitemmargin.fields.maximum' => HeaderFormat::amount(),
            'taskitemmargin.fields.value' => HeaderFormat::amount(),
        ]);

        foreach ($entities as $entity) {
            $sheet->getStyle([1, $row])
                ->getFont()->setBold(true);
            if ($entity->isEmpty()) {
                $sheet->setRowValues($row++, [
                    $entity->getName(),
                    $entity->getGroupCode(),
                    $entity->getCategoryCode(),
                    $entity->getUnit(),
                    $entity->getSupplier(),
                    $this->trans('task.edit.empty_items'),
                ])->mergeContent(6, 8, $row - 1);
            } else {
                $sheet->setRowValues($row++, [
                    $entity->getName(),
                    $entity->getGroupCode(),
                    $entity->getCategoryCode(),
                    $entity->getUnit(),
                    $entity->getSupplier(),
                ]);
            }

            foreach ($entity->getItems() as $item) {
                $sheet->getStyle([1, $row])
                    ->getAlignment()->setIndent(2);
                if ($item->isEmpty()) {
                    $sheet->setRowValues($row++, [
                        $item->getName(),
                        null,
                        null,
                        null,
                        $this->trans('taskitem.edit.empty_items'),
                    ])->mergeContent(6, 8, $row - 1);
                } else {
                    $index = 0;
                    foreach ($item->getMargins() as $margin) {
                        $text = (0 === $index++) ? $item->getName() : null;
                        $sheet->setRowValues($row++, [
                            $text,
                            null,
                            null,
                            null,
                            null,
                            $margin->getMinimum(),
                            $margin->getMaximum(),
                            $margin->getValue(),
                        ]);
                    }
                }
            }
        }
        $sheet->finish();

        return true;
    }
}
