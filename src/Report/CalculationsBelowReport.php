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

use App\Controller\AbstractController;
use App\Entity\Calculation;
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Traits\MathTrait;
use fpdf\PdfOrientation;
use fpdf\PdfTextAlignment;

/**
 * Report for the list of calculations.
 *
 * @extends AbstractArrayReport<Calculation>
 */
class CalculationsBelowReport extends AbstractArrayReport
{
    use MathTrait;

    /**
     * The sum of items calculations.
     */
    private float $items = 0.0;

    /**
     * The sum of overall calculations.
     */
    private float $overall = 0.0;

    /**
     * @param AbstractController $controller the parent controller
     * @param Calculation[]      $entities   the calculations to render
     */
    public function __construct(AbstractController $controller, array $entities)
    {
        parent::__construct($controller, $entities, PdfOrientation::LANDSCAPE);
    }

    protected function doRender(array $entities): bool
    {
        $this->addPage();
        $table = $this->createTable();
        $this->outputEntities($table, $entities);
        $this->outputTotal($table, $entities);

        return true;
    }

    private function createTable(): PdfTable
    {
        return PdfTable::instance($this)
            ->addColumns(
                PdfColumn::center($this->trans('calculation.fields.id'), 17, true),
                PdfColumn::center($this->trans('calculation.fields.date'), 20, true),
                PdfColumn::left($this->trans('calculation.fields.state'), 20, true),
                PdfColumn::left($this->trans('calculation.fields.customer'), 35),
                PdfColumn::left($this->trans('calculation.fields.description'), 65),
                PdfColumn::right($this->trans('report.calculation.amount'), 25, true),
                PdfColumn::right($this->trans('report.calculation.margin_percent'), 20, true),
                PdfColumn::right($this->trans('calculation.fields.total'), 25, true),
            )->outputHeaders();
    }

    /**
     * @psalm-param Calculation[] $entities
     */
    private function outputEntities(PdfTable $table, array $entities): void
    {
        $this->items = $this->overall = 0.0;
        $style = PdfStyle::getCellStyle()->setTextColor(PdfTextColor::red());
        foreach ($entities as $entity) {
            $this->outputEntity($table, $entity, $style);
        }
    }

    private function outputEntity(PdfTable $table, Calculation $entity, PdfStyle $style): void
    {
        $items = $entity->getItemsTotal();
        $overall = $entity->getOverallTotal();
        $table->startRow()
            ->add($entity->getFormattedId())
            ->add($entity->getFormattedDate())
            ->add($entity->getStateCode())
            ->add($entity->getCustomer())
            ->add($entity->getDescription())
            ->addAmount($items)
            ->addPercent($entity->getOverallMargin(), style: $style)
            ->addAmount($overall)
            ->endRow();

        $this->items += $items;
        $this->overall += $overall;
    }

    private function outputTotal(PdfTable $table, array $entities): void
    {
        $margins = $this->safeDivide($this->overall, $this->items);
        $text = $this->translateCount($entities, 'counters.calculations');
        $style = PdfStyle::getHeaderStyle()->setTextColor(PdfTextColor::red());
        /** @psalm-var positive-int $cols */
        $cols = $table->getColumnsCount() - 3;
        $table->getColumns()[0]->setAlignment(PdfTextAlignment::LEFT)
            ->setFixed(false);
        $table->startHeaderRow()
            ->add($text, $cols)
            ->addAmount($this->items)
            ->addPercent($margins, style: $style)
            ->addAmount($this->overall)
            ->endRow();
    }
}
