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
use App\Pdf\Enums\PdfDocumentOrientation;
use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\PdfColumn;
use App\Pdf\PdfException;
use App\Pdf\PdfGroupTable;
use App\Pdf\PdfStyle;
use App\Traits\MathTrait;
use App\Utils\FormatUtils;

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
        parent::__construct($controller, $entities, PdfDocumentOrientation::LANDSCAPE);
        $this->minMargin = $controller->getMinMargin();
        $this->setTitleTrans('calculation.list.title');
    }

    /**
     * @throws PdfException
     */
    protected function doRender(array $entities): bool
    {
        $this->AddPage();
        $table = $this->createTable();
        $this->outputEntities($table, $entities);
        $this->outputTotal($table, $entities);

        return true;
    }

    private function createTable(): PdfGroupTable
    {
        return PdfGroupTable::instance($this)
            ->addColumns(
                PdfColumn::center($this->trans('calculation.fields.id'), 17, true),
                PdfColumn::center($this->trans('calculation.fields.date'), 20, true),
                PdfColumn::left($this->trans('calculation.fields.customer'), 35),
                PdfColumn::left($this->trans('calculation.fields.description'), 65),
                PdfColumn::right($this->trans('report.calculation.amount'), 25, true),
                PdfColumn::right($this->trans('report.calculation.margin_percent'), 20, true),
                PdfColumn::right($this->trans('calculation.fields.total'), 25, true),
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
     * @psalm-param Calculation[] $entities
     *
     * @throws PdfException
     */
    private function outputEntities(PdfGroupTable $table, array $entities): void
    {
        $editable = null;
        $stateCode = null;
        $this->items = $this->overall = 0.0;
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

    private function outputEntity(PdfGroupTable $table, Calculation $entity): void
    {
        $items = $entity->getItemsTotal();
        $overall = $entity->getOverallTotal();
        $style = $this->getMarginStyle($entity);
        $table->startRow()
            ->add($entity->getFormattedId())
            ->add($entity->getFormattedDate())
            ->add($entity->getCustomer())
            ->add($entity->getDescription())
            ->add(FormatUtils::formatAmount($items))
            ->add(text: FormatUtils::formatPercent($entity->getOverallMargin()), style: $style)
            ->add(FormatUtils::formatAmount($overall))
            ->endRow();
        $this->items += $items;
        $this->overall += $overall;
    }

    private function outputTotal(PdfGroupTable $table, array $entities): void
    {
        $style = null;
        $margins = $this->safeDivide($this->overall, $this->items);
        if (!$this->isFloatZero($margins) && $margins < $this->minMargin) {
            $style = PdfStyle::getHeaderStyle()->setTextColor(PdfTextColor::red());
        }
        $text = $this->translateCount($entities, 'counters.calculations');
        /** @psalm-var positive-int $columns */
        $columns = $table->getColumnsCount() - 3;
        $table->getColumns()[0]->setAlignment(PdfTextAlignment::LEFT)
            ->setFixed(false);
        $table->startHeaderRow()
            ->add($text, $columns)
            ->add(FormatUtils::formatAmount($this->items))
            ->add(FormatUtils::formatPercent($margins), style: $style)
            ->add(FormatUtils::formatAmount($this->overall))
            ->endRow();
    }

    private function transEditable(bool $editable): string
    {
        return $this->trans($editable ? 'calculationstate.list.editable' : 'calculationstate.list.not_editable');
    }
}
