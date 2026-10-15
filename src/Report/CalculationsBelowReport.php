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

use App\Interfaces\DocumentHelperInterface;
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\PdfStyle;
use App\Report\Table\ReportTable;
use App\Repository\CalculationRepository;
use App\Traits\MathTrait;
use App\Utils\FormatUtils;
use fpdf\Enums\PdfMove;
use fpdf\Enums\PdfOrientation;
use fpdf\PdfDocument;

/**
 * Report for the list of calculations with a margin below.
 *
 * @phpstan-import-type ExportType from CalculationRepository
 */
class CalculationsBelowReport extends AbstractReport
{
    use MathTrait;

    /** The number of calculations */
    private int $count = 0;

    /** The sum of items. */
    private float $items = 0.0;

    /** The sum of overall calculations. */
    private float $overall = 0.0;

    /**
     * @param iterable<array> $entities the iterable calculations to render
     *
     * @phpstan-param iterable<ExportType> $entities
     */
    public function __construct(DocumentHelperInterface $helper, private readonly iterable $entities)
    {
        parent::__construct(helper: $helper, orientation: PdfOrientation::LANDSCAPE);
        $margin = FormatUtils::formatPercent($helper->getMinMargin());
        $this->setTranslatedTitle('below.title')
            ->setTranslatedDescription('below.description', ['%margin%' => $margin]);
    }

    #[\Override]
    public function render(): bool
    {
        $this->addPage();
        $this->outputEntities();
        $this->outputTotal();

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

    private function outputEntities(): void
    {
        $this->count = 0;
        $this->items = 0.0;
        $this->overall = 0.0;
        $table = $this->createTable();
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

    private function outputTotal(): void
    {
        $this->resetStyle();
        $text = $this->translateCount($this->count, 'counters.calculations');
        $this->useCellMargin(fn (): PdfDocument => $this->cell(text: $text, move: PdfMove::NEW_LINE));
    }
}
