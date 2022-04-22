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
use App\Pdf\Enums\PdfDocumentOrientation;
use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTableBuilder;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTextColor;
use App\Traits\MathTrait;
use App\Util\FormatUtils;

/**
 * Report for the list of calculations.
 *
 * @author Laurent Muller
 *
 * @extends AbstractArrayReport<Calculation>
 */
class CalculationsReport extends AbstractArrayReport
{
    use MathTrait;

    /**
     * The minimum margin style.
     */
    protected ?PdfStyle $marginStyle = null;

    /**
     * The minimum margin.
     */
    protected float $minMargin;

    /**
     * Constructor.
     *
     * @param AbstractController $controller the parent controller
     * @param Calculation[]      $entities   the calculations to render
     * @param bool               $grouped    true if calculations are grouped by state
     */
    public function __construct(AbstractController $controller, array $entities, protected bool $grouped = true)
    {
        parent::__construct($controller, $entities, PdfDocumentOrientation::LANDSCAPE);
        $this->minMargin = $controller->getApplication()->getMinMargin();
    }

    /**
     * {@inheritdoc}
     *
     * @param Calculation[] $entities
     */
    protected function doRender(array $entities): bool
    {
        // title
        if (empty($this->title)) {
            $this->setTitleTrans('calculation.list.title');
        }

        // new page
        $this->AddPage();

        // grouping?
        $table = $this->grouped ? $this->outputByGroup($entities) : $this->outputByList($entities);

        // totals
        $items = 0.0;
        $overall = 0.0;

        foreach ($entities as $entity) {
            $items += $entity->getItemsTotal();
            $overall += $entity->getOverallTotal();
        }
        $margins = $this->isFloatZero($items) ? 0 : $this->safeDivide($overall, $items);

        $text = $this->trans('common.count', [
            '%count%' => \count($entities),
        ]);

        $style = null;
        if (!$this->isFloatZero($margins) && $margins < $this->minMargin) {
            $style = PdfStyle::getHeaderStyle()->setTextColor(PdfTextColor::red());
        }

        $columns = $table->getColumnsCount() - 3;
        $table->getColumns()[0]->setAlignment(PdfTextAlignment::LEFT)
            ->setFixed(false);
        $table->startHeaderRow()
            ->add($text, $columns)
            ->add(FormatUtils::formatAmount($items))
            ->add(FormatUtils::formatPercent($margins), 1, $style)
            ->add(FormatUtils::formatAmount($overall))
            ->endRow();

        return true;
    }

    /**
     * Creates the table.
     *
     * @param bool $grouped true if calculations are grouped by state
     */
    private function createTable(bool $grouped): PdfGroupTableBuilder
    {
        // create table
        $columns = [
            PdfColumn::center($this->trans('calculation.fields.id'), 17, true),
            PdfColumn::center($this->trans('calculation.fields.date'), 20, true),
        ];
        if (!$grouped) {
            $columns[] = PdfColumn::left($this->trans('calculation.fields.state'), 12);
        }
        $columns = \array_merge($columns, [
            PdfColumn::left($this->trans('calculation.fields.customer'), 35),
            PdfColumn::left($this->trans('calculation.fields.description'), 65),
            PdfColumn::right($this->trans('report.calculation.amount'), 25, true),
            PdfColumn::right($this->trans('report.calculation.margin_percent'), 20, true),
            PdfColumn::right($this->trans('calculation.fields.total'), 25, true),
        ]);

        $table = new PdfGroupTableBuilder($this);
        $table->addColumns($columns)
            ->outputHeaders();

        return $table;
    }

    /**
     * Gets the style for the margin below.
     *
     * @param Calculation $calculation the calculation to get style for
     *
     * @return PdfStyle|null the margin style, if applicable, null otherwise
     */
    private function getMarginStyle(Calculation $calculation): ?PdfStyle
    {
        if ($calculation->isMarginBelow($this->minMargin)) {
            if (null === $this->marginStyle) {
                $this->marginStyle = PdfStyle::getCellStyle()->setTextColor(PdfTextColor::red());
            }

            return $this->marginStyle;
        }

        return null;
    }

    /**
     * Outputs the calculations grouped by state.
     *
     * @param Calculation[] $entities the calculations to render
     *
     * @return PdfGroupTableBuilder the table builder
     */
    private function outputByGroup(array $entities): PdfGroupTableBuilder
    {
        /** @var array<string, Calculation[]> $groups */
        $groups = [];
        foreach ($entities as $entity) {
            $key = (string) $entity->getStateCode();
            $groups[$key][] = $entity;
        }

        // create table
        $table = $this->createTable(true);

        // output
        foreach ($groups as $group => $items) {
            $table->setGroupKey($group);
            foreach ($items as $item) {
                $this->outputItem($table, $item, true);
            }
        }

        return $table;
    }

    /**
     * Ouput the calculations as list.
     *
     * @param Calculation[] $entities the calculations to render
     *
     * @return PdfGroupTableBuilder the table builder
     */
    private function outputByList(array $entities): PdfGroupTableBuilder
    {
        // create table
        $table = $this->createTable(false);

        // output
        foreach ($entities as $entity) {
            $this->outputItem($table, $entity, false);
        }

        return $table;
    }

    /**
     * Output a single calculation.
     *
     * @param PdfGroupTableBuilder $table        the table to write in
     * @param Calculation          $c            the calculation to output
     * @param bool                 $groupByState true if grouped by state
     */
    private function outputItem(PdfGroupTableBuilder $table, Calculation $c, bool $groupByState): void
    {
        // margin below style
        $style = $this->getMarginStyle($c);

        $table->startRow()
            ->add($c->getFormattedId())
            ->add($c->getFormattedDate());

        if (!$groupByState) {
            $table->add($c->getStateCode());
        }

        $table->add($c->getCustomer())
            ->add($c->getDescription())
            ->add(FormatUtils::formatAmount($c->getItemsTotal()))
            ->add(FormatUtils::formatPercent($c->getOverallMargin()), 1, $style)
            ->add(FormatUtils::formatAmount($c->getOverallTotal()))
            ->endRow();
    }
}
