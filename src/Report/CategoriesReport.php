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
use App\Entity\Category;
use App\Pdf\PdfColumn;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Utils\Utils;

/**
 * Report for the list of categories.
 *
 * @author Laurent Muller
 */
class CategoriesReport extends BaseReport
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
     * @param BaseController $controller the parent controller
     */
    public function __construct(BaseController $controller)
    {
        parent::__construct($controller);
        $this->SetTitleTrans('category.list.title', [], true);
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

        // sort
        Utils::sortField($categories, 'code');

        // new page
        $this->AddPage();

        // table
        $table = $this->createTable();

        // categories
        $marginsCount = 0;
        $last = \end($categories);
        foreach ($categories as $category) {
            $marginsCount += $this->outputCategory($table, $category);
            if ($category !== $last) {
                $table->singleLine();
            }
        }
        $this->resetStyle();

        // totals
        $txtCount = $this->trans('report.categories.count_category', [
            '%count%' => $count,
        ]);
        $txtMargin = $this->trans('report.categories.count_margin', [
            '%count%' => $marginsCount,
        ]);

        $this->SetY($this->GetY() + 1);

        $margin = $this->setCellMargin(0);
        $table->getColumns()[0]->setFixed(false);
        $table->startRow(PdfStyle::getNoBorderStyle())
            ->add($txtCount, 3)
            ->add($txtMargin, 3)
            ->endRow();
        $this->setCellMargin($margin);

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
        $table->addColumn(PdfColumn::left($this->trans('category.fields.code'), 40, true))
            ->addColumn(PdfColumn::left($this->trans('category.fields.description'), 50))
            ->addColumn(PdfColumn::right($this->trans('category.fields.products'), 20, true))
            ->addColumn(PdfColumn::right($this->trans('categorymargin.fields.minimum'), 25, true))
            ->addColumn(PdfColumn::right($this->trans('categorymargin.fields.maximum'), 25, true))
            ->addColumn(PdfColumn::right($this->trans('categorymargin.fields.margin'), 25, true))
            ->outputHeaders();

        return $table;
    }

    /**
     * Ouput a category.
     *
     * @param PdfTableBuilder $table    the table to render to
     * @param Category        $category the category to output
     *
     * @return int the number of margins
     */
    private function outputCategory(PdfTableBuilder $table, Category $category): int
    {
        $table->startRow()
            ->add($category->getCode())
            ->add($category->getDescription())
            ->add($this->localeInt($category->countProducts()));

        if ($category->hasMargins()) {
            $margins = $category->getMargins();
            $first = $margins->first();
            foreach ($margins as $margin) {
                if ($first !== $margin) {
                    $table->startRow()
                        ->add('')
                        ->add('')
                        ->add('');
                }
                $table->add($this->localeAmount($margin->getMinimum()))
                    ->add($this->localeAmount($margin->getMaximum()))
                    ->add($this->localePercent($margin->getMargin(), false))
                    ->endRow();
            }

            return $margins->count();
        }
        $empty = $this->trans('report.categories.empty_margins');
        $table->add($empty, 3)->endRow();

        return 0;
    }
}
