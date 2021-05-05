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

namespace App\Spreadsheet;

use App\Entity\Task;
use App\Entity\TaskItem;
use App\Entity\TaskItemMargin;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Excel document for the list of tasks.
 *
 * @author Laurent Muller
 */
class TaskDocument extends AbstractArrayDocument
{
    /**
     * @var bool
     */
    private $writeItem;

    /**
     * @var bool
     */
    private $writeTask;

    /**
     * {@inheritdoc}
     */
    public function setCellValue(Worksheet $sheet, int $columnIndex, int $rowIndex, $value): self
    {
        parent::setCellValue($sheet, $columnIndex, $rowIndex, $value);

        if (1 === $columnIndex) {
            if ($this->writeTask) {
                $sheet->getCellByColumnAndRow($columnIndex, $rowIndex)->getStyle()
                    ->getFont()->setBold(true);
            } elseif ($this->writeItem) {
                $sheet->getCellByColumnAndRow($columnIndex, $rowIndex)->getStyle()
                    ->getAlignment()->setIndent(2);
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        // initialize
        $this->start('task.list.title');
        $this->writeTask = $this->writeItem = false;

        // headers
        $this->setHeaderValues([
            'task.fields.name' => Alignment::HORIZONTAL_GENERAL,
            'task.fields.group' => Alignment::HORIZONTAL_GENERAL,
            'task.fields.category' => Alignment::HORIZONTAL_GENERAL,
            'task.fields.unit' => Alignment::HORIZONTAL_GENERAL,
            'taskitemmargin.fields.minimum' => Alignment::HORIZONTAL_RIGHT,
            'taskitemmargin.fields.maximum' => Alignment::HORIZONTAL_RIGHT,
            'taskitemmargin.fields.value' => Alignment::HORIZONTAL_RIGHT,
        ]);

        // formats
        $this->setFormatAmount(5)
            ->setFormatAmount(6)
            ->setFormatPrice(7);

        // rows
        $row = 2;
        /** @var Task $entity */
        foreach ($entities as $entity) {
            $this->writeTask = true;
            if ($entity->isEmpty()) {
                $this->setRowValues($row++, [
                    $entity->getName(),
                    $entity->getGroupCode(),
                    $entity->getCategoryCode(),
                    $entity->getUnit(),
                    $this->trans('task.edit.empty_items'),
                ]);
                $this->getActiveSheet()
                    ->mergeCellsByColumnAndRow(5, $row - 1, 7, $row - 1);
            } else {
                $this->setRowValues($row++, [
                    $entity->getName(),
                    $entity->getGroupCode(),
                    $entity->getCategoryCode(),
                    $entity->getUnit(),
                ]);
            }

            $this->writeTask = false;

            /** @var TaskItem $item */
            foreach ($entity->getItems() as $item) {
                $this->writeItem = true;
                if ($item->isEmpty()) {
                    $this->setRowValues($row++, [
                        $item->getName(),
                        null,
                        null,
                        null,
                        $this->trans('taskitem.edit.empty_items'),
                    ]);
                    $this->getActiveSheet()
                        ->mergeCellsByColumnAndRow(5, $row - 1, 7, $row - 1);
                    $this->writeItem = false;
                } else {
                    $index = 0;
                    /** @var TaskItemMargin $margin */
                    foreach ($item->getMargins() as $margin) {
                        $text = (0 === $index++) ? $item->getName() : null;
                        $this->setRowValues($row++, [
                            $text,
                            null,
                            null,
                            null,
                            $margin->getMinimum(),
                            $margin->getMaximum(),
                            $margin->getValue(),
                        ]);
                        $this->writeItem = false;
                    }
                }
            }
        }

        $this->finish();

        return true;
    }
}
