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
use App\Entity\Product;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTableBuilder;
use App\Utils\Utils;

/**
 * Report for the list of products.
 *
 * @author Laurent Muller
 */
class ProductsReport extends BaseReport
{
    /**
     * Set if the products are grouped by category.
     *
     * @var bool
     */
    protected $groupByCategory = false;

    /**
     * The products to render.
     *
     * @var \App\Entity\Product[]
     */
    protected $products;

    /**
     * Constructor.
     *
     * @param BaseController $controller the parent controller
     */
    public function __construct(BaseController $controller)
    {
        parent::__construct($controller);
        $this->setTitleTrans('product.list.title');
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        // products?
        $count = \count($this->products);
        if (0 === $count) {
            return false;
        }

        // new page
        $this->AddPage();

        // grouping?
        if ($this->groupByCategory) {
            $this->outputGroups($this->products);
        } else {
            $this->outputList($this->products);
        }

        // count
        return $this->renderCount($count);
    }

    /**
     * Sets if the products ar grouped by category.
     *
     * @param bool $groupByCategory true to group by category
     */
    public function setGroupByCategory(bool $groupByCategory): self
    {
        $this->groupByCategory = $groupByCategory;

        return $this;
    }

    /**
     * Sets the products to render.
     *
     * @param \App\Entity\Product[] $products
     */
    public function setProducts(array $products): self
    {
        $this->products = $products;

        return $this;
    }

    /**
     * Creates the table.
     *
     * @param bool $groupByCategory true if products are grouped by category
     */
    private function createTable($groupByCategory): PdfGroupTableBuilder
    {
        $columns = [
            PdfColumn::left($this->trans('product.fields.description'), 90),
            PdfColumn::left($this->trans('product.fields.supplier'), 45, true),
        ];
        if (!$groupByCategory) {
            $columns[] = PdfColumn::left($this->trans('product.fields.category'), 50, true);
        }
        $columns[] = PdfColumn::left($this->trans('product.fields.unit'), 20, true);
        $columns[] = PdfColumn::right($this->trans('product.fields.price'), 20, true);

        $table = new PdfGroupTableBuilder($this);
        $table->addColumns($columns)->outputHeaders();

        return $table;
    }

    /**
     * Groups the given products by category.
     *
     * @param Product[] $products the products to group
     *
     * @return array<string, Product[]> an array with category code as key, and corresponding products as value
     */
    private function groupProducts(array $products): array
    {
        $result = [];
        foreach ($products as $p) {
            $key = $p->getCategory()->getCode();
            $result[$key][] = $p;
        }

        // sort categories
        \ksort($result);

        // sort products
        foreach ($result as $key => &$value) {
            Utils::sortFields($value, [
                'description',
            ]);
        }

        return $result;
    }

    /**
     * Outputs the products grouped by category.
     *
     * @param \App\Entity\Calculation[] $products
     */
    private function outputGroups(array $products): void
    {
        // group
        $groups = $this->groupProducts($products);

        // create table
        $table = $this->createTable(true);

        // output
        foreach ($groups as $group => $list) {
            $table->setGroupName($group);
            foreach ($list as $p) {
                $table->startRow()
                    ->add($p->getDescription())
                    ->add($p->getSupplier())
                    ->add($p->getUnit())
                    ->add($this->localeAmount($p->getPrice()))
                    ->endRow();
            }
        }
    }

    /**
     * Ouput the products.
     *
     * @param \App\Entity\Product[] $products
     */
    private function outputList(array $products): void
    {
        // create table
        $table = $this->createTable(false);

        // sort
        Utils::sortFields($products, [
            'description',
        ]);

        // output
        foreach ($products as $p) {
            $table->startRow()
                ->add($p->getDescription())
                ->add($p->getSupplier())
                ->add($p->getCategory()
                ->getDescription())
                ->add($p->getUnit())
                ->add($this->localeAmount($p->getPrice()))
                ->endRow();
        }
    }
}
