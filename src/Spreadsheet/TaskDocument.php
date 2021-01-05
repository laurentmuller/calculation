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

        if (1 === $columnIndex && $this->writeTask) {
            $style = $sheet->getCellByColumnAndRow($columnIndex, $rowIndex)->getStyle();
            $style->getFont()->setBold(true);
        }

        if (1 === $columnIndex && $this->writeItem) {
            $style = $sheet->getCellByColumnAndRow($columnIndex, $rowIndex)->getStyle();
            $style->getAlignment()->setIndent(2);
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
            'task.fields.category' => Alignment::HORIZONTAL_GENERAL,
            'task.fields.unit' => Alignment::HORIZONTAL_GENERAL,
            'taskitemmargin.fields.minimum' => Alignment::HORIZONTAL_RIGHT,
            'taskitemmargin.fields.maximum' => Alignment::HORIZONTAL_RIGHT,
            'taskitemmargin.fields.value' => Alignment::HORIZONTAL_RIGHT,
        ]);

        // formats
        $this->setFormatAmount(4)
            ->setFormatAmount(5)
            ->setFormatAmount(6);

        // rows
        $row = 2;
        /** @var Task $entity */
        foreach ($entities as $entity) {
            $this->writeTask = true;
            $this->setRowValues($row++, [
                $entity->getName(),
                $entity->getCategoryCode(),
                $entity->getUnit(),
            ]);
            $this->writeTask = false;

            /** @var TaskItem $item */
            foreach ($entity->getItems() as $item) {
                $this->writeItem = true;
                if ($item->isEmpty()) {
                    $this->setRowValues($row++, [
                        $item->getName(),
                    ]);
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
