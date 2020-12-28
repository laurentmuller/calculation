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
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Excel document for the list of tasks.
 *
 * @author Laurent Muller
 */
class TaskDocument extends AbstractArrayDocument
{
    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        // initialize
        $this->start('task.list.title');

        // headers
        $this->setHeaderValues([
            'task.fields.name' => Alignment::HORIZONTAL_GENERAL,
            'task.fields.category' => Alignment::HORIZONTAL_GENERAL,
            'task.fields.unit' => Alignment::HORIZONTAL_GENERAL,
            'task.fields.items' => Alignment::HORIZONTAL_RIGHT,
        ]);

        // rows
        $row = 2;
        /** @var Task $entity */
        foreach ($entities as $entity) {
            $this->setRowValues($row++, [
                $entity->getName(),
                $entity->getCategoryCode(),
                $entity->getUnit(),
                $entity->count(),
            ]);
        }

        $this->finish();

        return true;
    }
}
