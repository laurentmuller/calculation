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

use App\Report\Table\ReportTable;

/**
 * Report for the list of global margins.
 *
 * @extends AbstractArrayReport<\App\Entity\GlobalMargin>
 */
class GlobalMarginsReport extends AbstractArrayReport
{
    protected function doRender(array $entities): bool
    {
        $this->setTitleTrans('globalmargin.list.title');
        $this->addPage();

        $table = $this->createTable();
        foreach ($entities as $entity) {
            $table->startRow()
                ->addCellAmount($entity->getMinimum())
                ->addCellAmount($entity->getMaximum())
                ->addCellAmount($entity->getDelta())
                ->addCellPercent($entity->getMargin())
                ->endRow();
        }

        return $this->renderCount($table, $entities, 'counters.margins');
    }

    private function createTable(): ReportTable
    {
        return ReportTable::fromReport($this)
            ->addColumns(
                $this->rightColumn('globalmargin.fields.minimum', 50),
                $this->rightColumn('globalmargin.fields.maximum', 50),
                $this->rightColumn('globalmargin.fields.delta', 50),
                $this->rightColumn('globalmargin.fields.margin', 50)
            )->outputHeaders();
    }
}
