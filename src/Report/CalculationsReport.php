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
use App\Pdf\PdfStyle;
use App\Report\Table\ReportGroupTable;
use App\Traits\MathTrait;
use fpdf\Enums\PdfOrientation;
use fpdf\Enums\PdfTextAlignment;

/**
 * Report for the list of calculations.
 *
 * @extends AbstractArrayReport<Calculation>
 */
class CalculationsReport extends AbstractArrayReport
{
    use MathTrait;

    /**
     * The sum of items calculations.
     */
    private float $items = 0.0;

    /**
     * The minimum margin style.
     */
    private ?PdfStyle $marginStyle = null;

    /**
     * The minimum margin.
     */
    private readonly float $minMargin;

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
        $this->minMargin = $controller->getMinMargin();
        $this->setTranslatedTitle('calculation.list.title');
    }

    #[\Override]
    protected function doRender(array $entities): bool
    {
        $this->addPage();
        $table = $this->createTable();
        $this->outputEntities($table, $entities);
        $this->outputTotal($table, $entities);

        return true;
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

    private function getMarginStyle(Calculation $calculation): ?PdfStyle
    {
        if (!$calculation->isMarginBelow($this->minMargin)) {
            return null;
        }
        if ($this->marginStyle instanceof PdfStyle) {
            return $this->marginStyle;
        }

        return $this->marginStyle = PdfStyle::getCellStyle()->setTextColor(PdfTextColor::red());
    }

    /**
     * @param Calculation[] $entities
     */
    private function outputEntities(ReportGroupTable $table, array $entities): void
    {
        $editable = null;
        $stateCode = null;
        $this->items = 0.0;
        $this->overall = 0.0;
        foreach ($entities as $entity) {
            if ($editable !== $entity->isEditable()) {
                $editable = $entity->isEditable();
                $this->addBookmark($this->transEditable($editable));
            }
            if ($stateCode !== $entity->getStateCode()) {
                $stateCode = $entity->getStateCode();
                $this->addBookmark(text: (string) $stateCode, level: 1);
                $table->setGroupKey($stateCode);
            }
            $this->outputEntity($table, $entity);
        }
    }

    private function outputEntity(ReportGroupTable $table, Calculation $entity): void
    {
        $items = $entity->getItemsTotal();
        $overall = $entity->getOverallTotal();
        $style = $this->getMarginStyle($entity);
        $table->startRow()
            ->add($entity->getFormattedId())
            ->add($entity->getFormattedDate())
            ->add($entity->getCustomer())
            ->add($entity->getDescription())
            ->addCellAmount($items)
            ->addCellPercent($entity->getOverallMargin(), style: $style)
            ->addCellAmount($overall)
            ->endRow();
        $this->items += $items;
        $this->overall += $overall;
    }

    private function outputTotal(ReportGroupTable $table, array $entities): void
    {
        $style = null;
        $margins = $this->safeDivide($this->overall, $this->items);
        if (!$this->isFloatZero($margins) && $margins < $this->minMargin) {
            $style = PdfStyle::getHeaderStyle()->setTextColor(PdfTextColor::red());
        }
        $text = $this->translateCount($entities, 'counters.calculations');
        /** @phpstan-var positive-int $columns */
        $columns = $table->getColumnsCount() - 3;
        $table->getColumns()[0]
            ->setAlignment(PdfTextAlignment::LEFT)
            ->setFixed(false);
        $table->startHeaderRow()
            ->add($text, $columns)
            ->addCellAmount($this->items)
            ->addCellPercent($margins, style: $style)
            ->addCellAmount($this->overall)
            ->endRow();
    }

    private function transEditable(bool $editable): string
    {
        return $this->trans(\sprintf('calculationstate.list.editable_%d', (int) $editable));
    }
}
