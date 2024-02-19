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
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Utils\FormatUtils;
use fpdf\PdfRectangleStyle;

/**
 * Report for the list of calculation states.
 *
 * @extends AbstractArrayReport<CalculationState>
 */
class CalculationStatesReport extends AbstractArrayReport implements PdfDrawCellBackgroundInterface
{
    private ?CalculationState $currentState = null;

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

    protected function doRender(array $entities): bool
    {
        $this->setTitleTrans('calculationstate.list.title');

        $this->addPage();
        $table = $this->createTable();

        foreach ($entities as $entity) {
            $this->currentState = $entity;
            $table->startRow()
                ->add($entity->getCode())
                ->add($entity->getDescription())
                ->add($this->formatEditable($entity->isEditable()))
                ->add(style: $this->getColorStyle($entity))
                ->add(FormatUtils::formatInt($entity->countCalculations()))
                ->endRow();
            $this->currentState = null;
        }

        return $this->renderCount($table, $entities, 'counters.states');
    }

    private function createTable(): PdfTable
    {
        return PdfTable::instance($this)
            ->setBackgroundListener($this)
            ->addColumns(
                PdfColumn::left($this->trans('calculationstate.fields.code'), 20),
                PdfColumn::left($this->trans('calculationstate.fields.description'), 80),
                PdfColumn::center($this->trans('calculationstate.fields.editable'), 20, true),
                PdfColumn::center($this->trans('calculationstate.fields.color'), 15, true),
                PdfColumn::right($this->trans('calculationstate.fields.calculations'), 22, true)
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
