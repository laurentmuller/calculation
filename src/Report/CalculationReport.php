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

use App\Controller\AbstractController;
use App\Entity\Calculation;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Pdf\PdfTextColor;
use App\Util\FormatUtils;

/**
 * Report for a calculation.
 *
 * @author Laurent Muller
 */
class CalculationReport extends AbstractReport
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
     * @param AbstractController $controller  the parent controller
     * @param Calculation        $calculation the calculation to render
     */
    public function __construct(AbstractController $controller, Calculation $calculation)
    {
        parent::__construct($controller);
        $this->minMargin = $controller->getApplication()->getMinMargin();
        $this->calculation = $calculation;
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
    public function Header(): void
    {
        parent::Header();
        $this->renderCalculation();
        $this->Ln(3);
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

        // update title
        if ($this->calculation->isNew()) {
            $this->setTitleTrans('calculation.add.title');
        } else {
            $id = FormatUtils::formatId($this->calculation->getId());
            $this->setTitleTrans('calculation.edit.title', ['%id%' => $id], true);
        }

        // new page
        $this->AddPage();

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
        $date = FormatUtils::formatDate($c->getDate());
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
        $globalAmount = $totalBrut * ($globalMargin - 1);

        $totalNet = $totalBrut + $globalAmount;
        $userMargin = $c->getUserMargin();
        $userAmount = $totalNet * $userMargin;

        // global margin
        $table->startRow()
            ->add($this->trans('calculation.fields.globalMargin'), 2)
            ->add(FormatUtils::formatPercent($globalMargin))
            ->add(FormatUtils::formatAmount($globalAmount), 2)
            ->endRow();

        // user margin
        if (!empty($userMargin)) {
            $table->startHeaderRow()
                ->add($this->trans('calculation.fields.totalNet'), 4)
                ->add(FormatUtils::formatAmount($totalNet))
                ->endRow();
            $table->startRow()
                ->add($this->trans('calculation.fields.userMargin'), 2)
                ->add(FormatUtils::formatPercent($userMargin))
                ->add(FormatUtils::formatAmount($userAmount), 2)
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
            ->add(FormatUtils::formatAmount($totalItems))
            ->add(FormatUtils::formatPercent($c->getOverallMargin()), 1, $style)
            ->add(FormatUtils::formatAmount($c->getOverallMarginAmount()))
            ->add(FormatUtils::formatAmount($c->getOverallTotal()))
            ->endRow();
    }
}
