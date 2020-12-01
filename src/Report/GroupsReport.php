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
use App\Entity\Category;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Util\FormatUtils;
use App\Util\Utils;

/**
 * Report for the list of categories.
 *
 * @author Laurent Muller
 */
class GroupsReport extends AbstractReport
{
    /**
     * The categories to render.
     *
     * @var \App\Entity\Category[]
     */
    protected $categories;

    /**
     * Constructor.
     *
     * @param AbstractController $controller the parent controller
     */
    public function __construct(AbstractController $controller)
    {
        parent::__construct($controller);
        $this->setTitleTrans('group.list.title', [], true);
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        // categories?
        $categories = $this->categories;
        $count = \count($categories);
        if (0 === $count) {
            return false;
        }

        // count values
        $marginsCount = 0;
        $categoriesCount = 0;
        foreach ($categories as $category) {
            $marginsCount += $category->countMargins();
            $categoriesCount += $category->countCategories();
        }

        // sort
        Utils::sortField($categories, 'code');

        // new page
        $this->AddPage();

        // table
        $table = $this->createTable();

        // categories
        $last = \end($categories);
        $emptyStyle = PdfStyle::getCellStyle()->setBorder('LR');
        foreach ($categories as $category) {
            $this->outputCategory($table, $category);
            if ($category !== $last) {
                $table->singleLine(null, $emptyStyle);
            }
        }
        $this->resetStyle();

        // totals
        $txtCount = $this->trans('counters.groups', [
            'count' => $count,
        ]);
        $txtCategory = $this->trans('counters.categories', [
            'count' => $categoriesCount,
        ]);
        $txtMargin = $this->trans('counters.margins', [
            'count' => $marginsCount,
        ]);

        $table = new PdfTableBuilder($this);
        $table->addColumn(PdfColumn::left(null, 20))
            ->addColumn(PdfColumn::right(null, 50, true))
            ->addColumn(PdfColumn::right(null, 62, true))
            ->startRow(PdfStyle::getNoBorderStyle())
            ->add($txtCount)
            ->add($txtCategory)
            ->add($txtMargin)
            ->endRow();

        return true;
    }

    /**
     * Sets the categories to render.
     *
     * @param \App\Entity\Category[] $categories
     */
    public function setCategories(array $categories): self
    {
        $this->categories = $categories;

        return $this;
    }

    /**
     * Creates the table builder.
     *
     * @return PdfTableBuilder the table
     */
    private function createTable(): PdfTableBuilder
    {
        $table = new PdfTableBuilder($this);
        $table->addColumn(PdfColumn::left($this->trans('group.fields.code'), 40, true))
            ->addColumn(PdfColumn::left($this->trans('group.fields.description'), 50))
            ->addColumn(PdfColumn::right($this->trans('group.fields.categories'), 25, true))
            ->addColumn(PdfColumn::right($this->trans('categorymargin.fields.minimum'), 22, true))
            ->addColumn(PdfColumn::right($this->trans('categorymargin.fields.maximum'), 22, true))
            ->addColumn(PdfColumn::right($this->trans('categorymargin.fields.margin'), 18, true))
            ->outputHeaders();

        return $table;
    }

    /**
     * Ouput a category.
     *
     * @param PdfTableBuilder $table    the table to render to
     * @param Category        $category the category to output
     */
    private function outputCategory(PdfTableBuilder $table, Category $category): void
    {
        $table->startRow()
            ->add($category->getCode())
            ->add($category->getDescription())
            ->add(FormatUtils::formatInt($category->countCategories()));

        if ($category->hasMargins()) {
            $skip = false;
            $margins = $category->getMargins();
            foreach ($margins as $margin) {
                if ($skip) {
                    $table->startRow()
                        ->add('')
                        ->add('')
                        ->add('');
                }
                $table->add(FormatUtils::formatAmount($margin->getMinimum()))
                    ->add(FormatUtils::formatAmount($margin->getMaximum()))
                    ->add(FormatUtils::formatPercent($margin->getMargin(), false))
                    ->endRow();
                $skip = true;
            }
        } else {
            $empty = $this->trans('report.groups.empty_margins');
            $table->add($empty, 3)->endRow();
        }
    }
}
