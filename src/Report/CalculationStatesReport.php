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

use App\Entity\CalculationState;
use App\Pdf\PdfBackgroundCell;
use App\Report\Table\ReportTable;
use fpdf\Color\PdfRgbColor;

/**
 * Report for the list of calculation states.
 *
 * @extends AbstractArrayReport<CalculationState>
 */
class CalculationStatesReport extends AbstractArrayReport
{
    #[\Override]
    protected function doRender(array $entities): bool
    {
        $this->setTranslatedTitle('calculationstate.list.title');

        $this->addPage();
        $table = $this->createTable();

        $total = 0;
        foreach ($entities as $entity) {
            $table->startRow()
                ->add($entity->getCode())
                ->add($entity->getDescription())
                ->add($this->formatEditable($entity))
                ->addCell($this->getColorCell($entity))
                ->addCellInt($entity->countCalculations())
                ->endRow();
            $total += $entity->countCalculations();
        }

        $table->startHeaderRow()
            ->add($this->translateCount($entities, 'counters.states'), 4)
            ->addCellInt($total)
            ->endRow();

        return true;
    }

    private function createTable(): ReportTable
    {
        return ReportTable::fromReport($this)
            ->addColumns(
                $this->leftColumn('calculationstate.fields.code', 20),
                $this->leftColumn('calculationstate.fields.description', 80),
                $this->centerColumn('calculationstate.fields.editable', 20, true),
                $this->centerColumn('calculationstate.fields.color', 15, true),
                $this->rightColumn('calculationstate.fields.calculations', 22, true)
            )->outputHeaders();
    }

    private function formatEditable(CalculationState $entity): string
    {
        return $this->trans($entity->isEditable() ? 'common.value_true' : 'common.value_false');
    }

    private function getColorCell(CalculationState $state): PdfBackgroundCell
    {
        return new PdfBackgroundCell($state->getRgbColor() ?? PdfRgbColor::white());
    }
}
