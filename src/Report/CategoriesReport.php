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
use App\Pdf\PdfGroupTableBuilder;
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
        $categoriesCount = \count($categories);
        if (0 === $categoriesCount) {
            return false;
        }

        // sort
        Utils::sortField($categories, 'code');

        // group by parent code
        $groups = Utils::groupBy($categories, function (Category $category) {
            return $category->getParent()->getCode();
        });

        // count values
        $productsCount = \array_reduce($categories, function (int $carry, Category $category) {
            return $carry + $category->countProducts();
        }, 0);

        // new page
        $this->AddPage();

        // table
        $table = $this->createTable();

        // categories by group
        $style = PdfStyle::getCellStyle()->setIndent(2);
        foreach ($groups as $key => $items) {
            $table->setGroupKey($key);
            foreach ($items as $item) {
                $table->startRow()
                    ->add($item->getCode(), 1, $style)
                    ->add($item->getDescription())
                    ->add(FormatUtils::formatInt($item->countProducts()))
                    ->endRow();
            }
        }
        $this->resetStyle();

        // totals
        $txtGroup = $this->trans('counters.groups', [
            'count' => \count($groups),
        ]);
        $txtCount = $this->trans('counters.categories', [
            'count' => $categoriesCount,
        ]);
        $txtProduct = $this->trans('counters.products', [
            'count' => $productsCount,
        ]);

        $table = new PdfTableBuilder($this);
        $table->addColumn(PdfColumn::left(null, 40, true))
            ->addColumn(PdfColumn::center(null, 20))
            ->addColumn(PdfColumn::right(null, 20))
            ->startRow(PdfStyle::getNoBorderStyle())
            ->add($txtGroup)
            ->add($txtCount)
            ->add($txtProduct)
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
     */
    private function createTable(): PdfGroupTableBuilder
    {
        $table = new PdfGroupTableBuilder($this);
        $table->addColumn(PdfColumn::left($this->trans('category.fields.code'), 40, true))
            ->addColumn(PdfColumn::left($this->trans('category.fields.description'), 50))
            ->addColumn(PdfColumn::right($this->trans('category.fields.products'), 15, true))
            ->outputHeaders();

        return $table;
    }
}
