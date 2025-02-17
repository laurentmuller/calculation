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
use App\Pdf\Colors\PdfFillColor;
use App\Pdf\Events\PdfCellBackgroundEvent;
use App\Pdf\Interfaces\PdfDrawCellBackgroundInterface;
use App\Pdf\PdfStyle;
use App\Report\Table\ReportTable;
use fpdf\Enums\PdfRectangleStyle;

/**
 * Report for the list of calculation states.
 *
 * @extends AbstractArrayReport<CalculationState>
 */
class CalculationStatesReport extends AbstractArrayReport implements PdfDrawCellBackgroundInterface
{
    private ?CalculationState $currentState = null;

    #[\Override]
    public function drawCellBackground(PdfCellBackgroundEvent $event): bool
    {
        if (3 !== $event->index || !$this->currentState instanceof CalculationState) {
            return false;
        }

        $parent = $event->getDocument();
        $margin = $parent->getCellMargin();
        $bounds = $event->bounds->inflateXY(-3.0 * $margin, -$margin);
        $bounds->height = self::LINE_HEIGHT - 2.0 * $margin;
        $parent->rectangle($bounds, PdfRectangleStyle::BOTH);

        return true;
    }

    #[\Override]
    protected function doRender(array $entities): bool
    {
        $this->setTitleTrans('calculationstate.list.title');

        $this->addPage();
        $table = $this->createTable();

        $total = 0;
        foreach ($entities as $entity) {
            $this->currentState = $entity;
            $table->startRow()
                ->add($entity->getCode())
                ->add($entity->getDescription())
                ->add($this->formatEditable($entity->isEditable()))
                ->add(style: $this->getColorStyle($entity))
                ->addCellInt($entity->countCalculations())
                ->endRow();
            $this->currentState = null;
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
            ->setBackgroundListener($this)
            ->addColumns(
                $this->leftColumn('calculationstate.fields.code', 20),
                $this->leftColumn('calculationstate.fields.description', 80),
                $this->centerColumn('calculationstate.fields.editable', 20, true),
                $this->centerColumn('calculationstate.fields.color', 15, true),
                $this->rightColumn('calculationstate.fields.calculations', 22, true)
            )->outputHeaders();
    }

    private function formatEditable(bool $editable): string
    {
        return $this->trans($editable ? 'common.value_true' : 'common.value_false');
    }

    /**
     * Gets the cell style for the given state color.
     *
     * @param CalculationState $state the state to get style for
     *
     * @return PdfStyle|null the style, if applicable, null otherwise
     */
    private function getColorStyle(CalculationState $state): ?PdfStyle
    {
        $color = PdfFillColor::create($state->getColor());
        if ($color instanceof PdfFillColor) {
            return PdfStyle::getCellStyle()->setFillColor($color);
        }

        return null;
    }
}
