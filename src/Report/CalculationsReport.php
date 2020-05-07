<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Report;

use App\Controller\BaseController;
use App\Entity\Calculation;
use App\Pdf\PdfConstantsInterface;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTableBuilder;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTextColor;
use App\Traits\MathTrait;

/**
 * Report for the list of calculations.
 *
 * @author Laurent Muller
 */
class CalculationsReport extends BaseReport
{
    use MathTrait;

    /**
     * The calculations to render.
     *
     * @var \App\Entity\Calculation[]
     */
    protected $calculations;

    /**
     * Set if the calculations are grouped by state.
     *
     * @var bool
     */
    protected $grouped = false;

    /**
     * The minimum margin style.
     *
     * @var PdfStyle|null
     */
    protected $marginStyle;

    /**
     * The minimum margin.
     *
     * @var float
     */
    protected $minMargin;

    /**
     * Constructor.
     *
     * @param BaseController $controller the parent controller
     */
    public function __construct(BaseController $controller)
    {
        parent::__construct($controller, self::ORIENTATION_LANDSCAPE);

        $this->setTitleTrans('calculation.list.title');
        $this->minMargin = $controller->getApplication()->getMinMargin();
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        // calculations?
        if (empty($this->calculations)) {
            return false;
        }

        // new page
        $this->AddPage();

        // grouping?
        if ($this->grouped) {
            $table = $this->outputByGroup();
        } else {
            $table = $this->outputByList();
        }

        // totals
        $items = 0.0;
        $overall = 0.0;
        foreach ($this->calculations as $c) {
            $items += $c->getItemsTotal();
            $overall += $c->getOverallTotal();
        }
        $margins = $this->isFloatZero($items) ? 0 : $this->safeDivide($overall, $items) - 1;

        $text = $this->trans('common.count', [
            '%count%' => \count($this->calculations),
        ]);

        $columns = $table->getColumnsCount() - 3;
        $table->getColumns()[0]->setAlignment(PdfConstantsInterface::ALIGN_LEFT)
            ->setFixed(false);
        $table->startHeaderRow()
            ->add($text, $columns)
            ->add($this->localeAmount($items))
            ->add($this->localePercent($margins))
            ->add($this->localeAmount($overall))
            ->endRow();

        return true;
    }

    /**
     * Sets the calculations to render.
     *
     * @param \App\Entity\Calculation[] $calculations the calculations to render
     */
    public function setCalculations(array $calculations): self
    {
        $this->calculations = $calculations;

        return $this;
    }

    /**
     * Sets a value indicating if calculations are grouped by state.
     *
     * @param bool $grouped true if grouped by state
     */
    public function setGrouped(bool $grouped): self
    {
        $this->grouped = $grouped;

        return $this;
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
            $columns[] = PdfColumn::left($this->trans('calculation.fields.state'), 12, false);
        }
        $columns = \array_merge($columns, [
            PdfColumn::left($this->trans('calculation.fields.customer'), 50, false),
            PdfColumn::left($this->trans('calculation.fields.description'), 50, false),
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
            if (!$this->marginStyle) {
                $this->marginStyle = PdfStyle::getCellStyle()->setTextColor(PdfTextColor::red());
            }

            return $this->marginStyle;
        }

        return null;
    }

    /**
     * Outputs the calculations grouped by state.
     *
     * @return PdfGroupTableBuilder the table builder
     */
    private function outputByGroup(): PdfGroupTableBuilder
    {
        // groups the calculations by state
        $groups = [];
        foreach ($this->calculations as $c) {
            $key = $c->getStateCode();
            $groups[$key][] = $c;
        }

        // create table
        $table = $this->createTable(true);

        // output
        foreach ($groups as $group => $items) {
            $table->setGroupName($group);
            foreach ($items as $item) {
                $this->outputItem($table, $item, true);
            }
        }

        return $table;
    }

    /**
     * Ouput the calculations as list.
     *
     * @return PdfGroupTableBuilder the table builder
     */
    private function outputByList(): PdfGroupTableBuilder
    {
        // create table
        $table = $this->createTable(false);

        // output
        foreach ($this->calculations as $c) {
            $this->outputItem($table, $c, false);
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
            ->add($this->localeId($c->getId()))
            ->add($this->localeDate($c->getDate()));

        if (!$groupByState) {
            $table->add($c->getStateCode());
        }

        $table->add($c->getCustomer())
            ->add($c->getDescription())
            ->add($this->localeAmount($c->getItemsTotal()))
            ->add($this->localePercent($c->getOverallMargin()), 1, $style)
            ->add($this->localeAmount($c->getOverallTotal()))
            ->endRow();
    }
}
