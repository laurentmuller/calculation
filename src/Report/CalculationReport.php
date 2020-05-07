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
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Pdf\PdfTextColor;

/**
 * Report for a calculation.
 *
 * @author Laurent Muller
 */
class CalculationReport extends BaseReport
{
    /**
     * The calculation.
     *
     * @var \App\Entity\Calculation
     */
    protected $calculation;

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
        parent::__construct($controller);
        $this->minMargin = $controller->getApplication()->getMinMargin();
    }

    /**
     * Gets the calculation.
     *
     * @return Calculation
     */
    public function getCalculation(): ?Calculation
    {
        return $this->calculation;
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        // calculation?
        if (null === $this->calculation) {
            return false;
        }

        // new page
        $this->AddPage();

        // calculation
        $this->renderCalculation();
        $this->Ln(3);

        // groups
        $groups = $this->calculation->getGroups();
        if ($groups->isEmpty()) {
            $this->resetStyle()->Ln();
            $message = $this->trans('calculation.edit.empty');
            $this->Cell(0, 0, $message, self::BORDER_NONE, self::MOVE_TO_NEW_LINE, self::ALIGN_CENTER);

            return true;
        }

        // items
        CalculationGroupTable::render($this);
        $this->Ln(3);

        // check if margin groups and overall total can fit in the current page
        $lines = $groups->count() + 2;
        $lines += empty($this->calculation->getUserMargin()) ? 2 : 4;
        if (!$this->isPrintable(2 + self::LINE_HEIGHT * $lines)) {
            $this->AddPage();
        }

        // total by group
        CalculationTotalTable::render($this);

        // // overall total
        $this->renderOverall();

        return true;
    }

    /**
     * Sets the calculation to render.
     */
    public function setCalculation(Calculation $calculation): self
    {
        $this->calculation = $calculation;

        // update title
        if ($calculation->isNew()) {
            $this->setTitleTrans('calculation.add.title');
        } else {
            $id = $this->localeId($calculation->getId());
            $this->setTitleTrans('calculation.edit.title', ['%id%' => $id], true);
        }

        return $this;
    }

    /**
     * Render the calculation.
     */
    private function renderCalculation(): void
    {
        $c = $this->calculation;

        $columns = [
            PdfColumn::left(null, 100),
            PdfColumn::right(null, 40, true),
        ];

        $state = $c->getStateCode();
        $date = $this->localeDate($c->getDate());
        $style = PdfStyle::getHeaderStyle()->setFontRegular()
            ->setBorder('tbr');

        $table = new PdfTableBuilder($this);
        $table->setHeaderStyle(PdfStyle::getHeaderStyle()->setBorder('tbl'));
        $table->addColumns($columns)
            ->startHeaderRow()
            ->add($c->getCustomer())
            ->add($state, 1, $style)
            ->endRow()

            ->startHeaderRow()
            ->add($c->getDescription())
            ->add($date, 1, $style)
            ->endRow();
    }

    /**
     * Render the overall total table.
     */
    private function renderOverall(): void
    {
        $c = $this->calculation;
        $columns = [
            PdfColumn::left(null, 40, false),
            PdfColumn::right(null, 20, true),
            PdfColumn::right(null, 20, true),
            PdfColumn::right(null, 20, true),
            PdfColumn::right(null, 20, true),
        ];
        $table = new PdfTableBuilder($this);
        $table->addColumns($columns)->setRepeatHeader(false);

        // compute values
        $totalItems = $c->getGroupsAmount();
        $totalMargins = $c->getGroupsMarginAmount();
        $totalBrut = $totalItems + $totalMargins;

        $globalMargin = $c->getGlobalMargin();
        $globalAmount = $totalBrut * $globalMargin;

        $totalNet = $totalBrut + $globalAmount;
        $userMargin = $c->getUserMargin();
        $userAmount = $totalNet * $userMargin;

        // global margin
        $table->startRow()
            ->add($this->trans('calculation.fields.globalMargin'), 2)
            ->add($this->localePercent($globalMargin))
            ->add($this->localeAmount($globalAmount), 2)
            ->endRow();

        // user margin
        if (!empty($userMargin)) {
            $table->startHeaderRow()
                ->add($this->trans('calculation.fields.totalNet'), 4)
                ->add($this->localeAmount($totalNet))
                ->endRow();
            $table->startRow()
                ->add($this->trans('calculation.fields.userMargin'), 2)
                ->add($this->localePercent($userMargin))
                ->add($this->localeAmount($userAmount), 2)
                ->endRow();
        }

        // style for margin
        $style = null;
        if ($c->isMarginBelow($this->minMargin)) {
            $style = PdfStyle::getHeaderStyle()->setTextColor(PdfTextColor::red());
        }

        // overall margin and amouts
        $table->startHeaderRow()
            ->add($this->trans('calculation.fields.overallTotal'))
            ->add($this->localeAmount($totalItems))
            ->add($this->localePercent($c->getOverallMargin()), 1, $style)
            ->add($this->localeAmount($c->getOverallMarginAmount()))
            ->add($this->localeAmount($c->getOverallTotal()))
            ->endRow();
    }
}
