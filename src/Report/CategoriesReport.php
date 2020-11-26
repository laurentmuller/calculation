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
class CategoriesReport extends AbstractReport
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
        $this->setTitleTrans('category.list.title', [], true);
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
        $productsCount = 0;
        foreach ($categories as $category) {
            $marginsCount += $category->countMargins();
            $productsCount += $category->countProducts();
        }

        // sort
        Utils::sortField($categories, 'code');

        // new page
        $this->AddPage();

        // table
        $table = $this->createTable();

        // categories
        $last = \end($categories);
        $codeStyle = PdfStyle::getCellStyle()->setFontBold();
        $emptyStyle = PdfStyle::getCellStyle()->setBorder('LR');
        foreach ($categories as $category) {
            $this->outputCategory($table, $category, $codeStyle);
            if ($category !== $last) {
                $table->singleLine(null, $emptyStyle);
            }
        }
        $this->resetStyle();

        // totals
        $txtCount = $this->trans('report.categories.count_category', [
            '%count%' => $count,
        ]);
        $txtProduct = $this->trans('report.categories.count_product', [
            '%count%' => $productsCount,
        ]);
        $txtMargin = $this->trans('report.categories.count_margin', [
            '%count%' => $marginsCount,
        ]);

        $table = new PdfTableBuilder($this);
        $table->addColumn(PdfColumn::left(null, 20))
            ->addColumn(PdfColumn::center(null, 20))
            ->addColumn(PdfColumn::right(null, 20))
            ->startRow(PdfStyle::getNoBorderStyle())
            ->add($txtCount)
            ->add($txtProduct)
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
        $table->addColumn(PdfColumn::left($this->trans('category.fields.code'), 40, true))
            ->addColumn(PdfColumn::left($this->trans('category.fields.description'), 50))
            ->addColumn(PdfColumn::right($this->trans('category.fields.products'), 15, true))
            ->addColumn(PdfColumn::right($this->trans('categorymargin.fields.minimum'), 22, true))
            ->addColumn(PdfColumn::right($this->trans('categorymargin.fields.maximum'), 22, true))
            ->addColumn(PdfColumn::right($this->trans('categorymargin.fields.margin'), 18, true))
            ->outputHeaders();

        return $table;
    }

    /**
     * Ouput a category.
     *
     * @param PdfTableBuilder $table     the table to render to
     * @param Category        $category  the category to output
     * @param PdfStyle        $codeStyle the style for the category code
     */
    private function outputCategory(PdfTableBuilder $table, Category $category, PdfStyle $codeStyle): void
    {
        $table->startRow()
            ->add($category->getCode(), 1, $codeStyle)
            ->add($category->getDescription())
            ->add(FormatUtils::formatInt($category->countProducts()));

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
            $empty = $this->trans('report.categories.empty_margins');
            $table->add($empty, 3)->endRow();
        }
    }
}
