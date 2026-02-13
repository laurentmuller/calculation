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
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\PdfStyle;
use App\Report\Table\ReportTable;
use App\Repository\CalculationRepository;
use App\Traits\MathTrait;
use App\Utils\FormatUtils;
use fpdf\Enums\PdfOrientation;
use fpdf\Enums\PdfTextAlignment;

/**
 * Report for the list of calculations with margin below.
 *
 * @phpstan-import-type ExportType from CalculationRepository
 */
class CalculationsBelowReport extends AbstractReport
{
    use MathTrait;

    /** The number of calculations */
    private int $count = 0;

    /** The sum of items calculations. */
    private float $items = 0.0;

    /** The sum of overall calculations. */
    private float $overall = 0.0;

    /**
     * @param AbstractController $controller the parent controller
     * @param iterable<array>    $entities   the iterable calculations to render
     *
     * @phpstan-param iterable<ExportType> $entities
     */
    public function __construct(AbstractController $controller, private readonly iterable $entities)
    {
        parent::__construct(controller: $controller, orientation: PdfOrientation::LANDSCAPE);
        $margin = FormatUtils::formatPercent($controller->getMinMargin());
        $this->setTranslatedTitle('below.title')
            ->setTranslatedDescription('below.description', ['%margin%' => $margin]);
    }

    #[\Override]
    public function render(): bool
    {
        $this->addPage();
        $table = $this->createTable();
        $this->outputEntities($table);
        $this->outputTotal($table);

        return $this->count > 0;
    }

    private function createTable(): ReportTable
    {
        return ReportTable::fromReport($this)
            ->addColumns(
                $this->centerColumn('calculation.fields.id', 17, true),
                $this->centerColumn('calculation.fields.date', 20, true),
                $this->leftColumn('calculation.fields.state', 20, true),
                $this->leftColumn('calculation.fields.customer', 35),
                $this->leftColumn('calculation.fields.description', 65),
                $this->rightColumn('report.calculation.amount', 25, true),
                $this->rightColumn('report.calculation.margin_percent', 20, true),
                $this->rightColumn('calculation.fields.total', 25, true),
            )->outputHeaders();
    }

    private function outputEntities(ReportTable $table): void
    {
        $this->count = 0;
        $this->items = 0.0;
        $this->overall = 0.0;
        $style = PdfStyle::getCellStyle()->setTextColor(PdfTextColor::red());
        foreach ($this->entities as $entity) {
            $this->outputEntity($table, $entity, $style);
        }
    }

    /**
     * @phpstan-param ExportType $entity
     */
    private function outputEntity(ReportTable $table, array $entity, PdfStyle $style): void
    {
        $itemsTotal = $entity['itemsTotal'];
        $overallTotal = $entity['overallTotal'];
        $margins = $this->getSafeMargin($overallTotal, $itemsTotal);

        $table->startRow()
            ->add(FormatUtils::formatId($entity['id']))
            ->add(FormatUtils::formatDate($entity['date']))
            ->add($entity['code'])
            ->add($entity['customer'])
            ->add($entity['description'])
            ->addCellAmount($itemsTotal)
            ->addCellPercent($margins, style: $style)
            ->addCellAmount($overallTotal)
            ->endRow();

        ++$this->count;
        $this->items += $itemsTotal;
        $this->overall += $overallTotal;
    }

    private function outputTotal(ReportTable $table): void
    {
        $margins = $this->safeDivide($this->overall, $this->items);
        $text = $this->translateCount($this->count, 'counters.calculations');
        $style = PdfStyle::getHeaderStyle()->setTextColor(PdfTextColor::red());
        /** @var positive-int $cols */
        $cols = $table->getColumnsCount() - 3;
        $table->getColumns()[0]
            ->setAlignment(PdfTextAlignment::LEFT)
            ->setFixed(false);
        $table->startHeaderRow()
            ->add($text, $cols)
            ->addCellAmount($this->items)
            ->addCellPercent($margins, style: $style)
            ->addCellAmount($this->overall)
            ->endRow();
    }
}
