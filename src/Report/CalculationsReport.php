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
use App\Report\Table\ReportGroupTable;
use App\Repository\CalculationRepository;
use App\Traits\MathTrait;
use App\Utils\FormatUtils;
use fpdf\Enums\PdfOrientation;
use fpdf\Enums\PdfTextAlignment;

/**
 * Report for the list of calculations.
 *
 * @phpstan-import-type ExportType from CalculationRepository
 */
class CalculationsReport extends AbstractReport
{
    use MathTrait;

    /** The number of calculations */
    private int $count = 0;

    /** The sum of items calculations. */
    private float $items = 0.0;

    /** The minimum margin style. */
    private ?PdfStyle $marginStyle = null;

    /** The minimum margin. */
    private readonly float $minMargin;

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
        $this->minMargin = $controller->getMinMargin();
        $this->setTranslatedTitle('calculation.list.title');
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

    private function createTable(): ReportGroupTable
    {
        return ReportGroupTable::fromReport($this)
            ->addColumns(
                $this->centerColumn('calculation.fields.id', 17, true),
                $this->centerColumn('calculation.fields.date', 20, true),
                $this->leftColumn('calculation.fields.customer', 35),
                $this->leftColumn('calculation.fields.description', 65),
                $this->rightColumn('report.calculation.amount', 25, true),
                $this->rightColumn('report.calculation.margin_percent', 20, true),
                $this->rightColumn('calculation.fields.total', 25, true),
            )->outputHeaders();
    }

    private function getMarginStyle(float $margin): ?PdfStyle
    {
        if (!$this->isBelow($this->minMargin, $margin)) {
            return null;
        }

        return $this->marginStyle ??= PdfStyle::getCellStyle()->setTextColor(PdfTextColor::red());
    }

    private function outputEntities(ReportGroupTable $table): void
    {
        $editable = null;
        $stateCode = null;
        $this->count = 0;
        $this->items = 0.0;
        $this->overall = 0.0;
        foreach ($this->entities as $entity) {
            // editable group
            if ($editable !== $entity['editable']) {
                $editable = $entity['editable'];
                $this->addBookmark($this->transEditable($editable));
            }
            // state group
            if ($stateCode !== $entity['code']) {
                $stateCode = $entity['code'];
                $this->addBookmark(text: $stateCode, level: 1);
                $table->setGroupKey($stateCode);
            }
            $this->outputEntity($table, $entity);
        }
    }

    /**
     * @phpstan-param ExportType $entity
     */
    private function outputEntity(ReportGroupTable $table, array $entity): void
    {
        $itemsTotal = $entity['itemsTotal'];
        $overallTotal = $entity['overallTotal'];
        $margins = $this->getSafeMargin($overallTotal, $itemsTotal);
        $style = $this->getMarginStyle($margins);
        $table->startRow()
            ->add(FormatUtils::formatId($entity['id']))
            ->add(FormatUtils::formatDate($entity['date']))
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

    private function outputTotal(ReportGroupTable $table): void
    {
        $style = null;
        $margin = $this->safeDivide($this->overall, $this->items);
        if ($this->isBelow($this->minMargin, $margin)) {
            $style = PdfStyle::getHeaderStyle()->setTextColor(PdfTextColor::red());
        }
        $text = $this->translateCount($this->count, 'counters.calculations');
        /** @var positive-int $columns */
        $columns = $table->getColumnsCount() - 3;
        $table->getColumns()[0]
            ->setAlignment(PdfTextAlignment::LEFT)
            ->setFixed(false);
        $table->startHeaderRow()
            ->add($text, $columns)
            ->addCellAmount($this->items)
            ->addCellPercent($margin, style: $style)
            ->addCellAmount($this->overall)
            ->endRow();
    }

    private function transEditable(bool $editable): string
    {
        return $this->trans(\sprintf('calculationstate.list.editable_%d', (int) $editable));
    }
}
