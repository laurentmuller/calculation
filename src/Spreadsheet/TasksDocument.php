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

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Spreadsheet document for the list of tasks.
 *
 * @extends AbstractArrayDocument<\App\Entity\Task>
 */
class TasksDocument extends AbstractArrayDocument
{
    private bool $writeItem = false;

    private bool $writeTask = false;

    /**
     * {@inheritdoc}
     */
    public function setCellValue(Worksheet $sheet, int $columnIndex, int $rowIndex, $value): static
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
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
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
            'task.fields.supplier' => Alignment::HORIZONTAL_GENERAL,
            'taskitemmargin.fields.minimum' => Alignment::HORIZONTAL_RIGHT,
            'taskitemmargin.fields.maximum' => Alignment::HORIZONTAL_RIGHT,
            'taskitemmargin.fields.value' => Alignment::HORIZONTAL_RIGHT,
        ]);

        // formats
        $this->setFormatAmount(6)
            ->setFormatAmount(7)
            ->setFormatPrice(8);

        // rows
        $row = 2;
        foreach ($entities as $entity) {
            $this->writeTask = true;
            if ($entity->isEmpty()) {
                $this->setRowValues($row++, [
                    $entity->getName(),
                    $entity->getGroupCode(),
                    $entity->getCategoryCode(),
                    $entity->getUnit(),
                    $entity->getSupplier(),
                    $this->trans('task.edit.empty_items'),
                ]);
                $this->mergeCells(6, 8, $row - 1);
            } else {
                $this->setRowValues($row++, [
                    $entity->getName(),
                    $entity->getGroupCode(),
                    $entity->getCategoryCode(),
                    $entity->getUnit(),
                    $entity->getSupplier(),
                ]);
            }
            $this->writeTask = false;

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
                    $this->mergeCells(6, 8, $row - 1);
                    $this->writeItem = false;
                } else {
                    $index = 0;
                    foreach ($item->getMargins() as $margin) {
                        $text = (0 === $index++) ? $item->getName() : null;
                        $this->setRowValues($row++, [
                            $text,
                            null,
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
