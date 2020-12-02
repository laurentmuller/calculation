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
use App\Entity\Product;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTableBuilder;
use App\Util\FormatUtils;

/**
 * Report for the list of products.
 *
 * @author Laurent Muller
 */
class ProductsReport extends AbstractReport
{
    /**
     * The products to render.
     *
     * @var Product[]
     */
    protected $products;

    /**
     * Constructor.
     *
     * @param AbstractController $controller the parent controller
     */
    public function __construct(AbstractController $controller)
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

        // create table
        $table = $this->createTable();

        // render
        foreach ($this->products as $product) {
            // group
            $key = \sprintf('%s - %s', $product->getParentCode(), $product->getCategoryCode());
            $table->setGroupKey($key);

            // product
            $table->startRow()
                ->add($product->getDescription())
                ->add($product->getSupplier())
                ->add($product->getUnit())
                ->add(FormatUtils::formatAmount($product->getPrice()))
                ->endRow();
        }

        // count
        return $this->renderCount($count);
    }

    /**
     * Sets the products to render.
     *
     * @param Product[] $products
     */
    public function setProducts(array $products): self
    {
        $this->products = $products;

        return $this;
    }

    /**
     * Creates the table.
     */
    private function createTable(): PdfGroupTableBuilder
    {
        $columns = [
            PdfColumn::left($this->trans('product.fields.description'), 90),
            PdfColumn::left($this->trans('product.fields.supplier'), 45, true),
            PdfColumn::left($this->trans('product.fields.unit'), 20, true),
            PdfColumn::right($this->trans('product.fields.price'), 20, true),
        ];

        $table = new PdfGroupTableBuilder($this);
        $table->addColumns($columns)->outputHeaders();

        return $table;
    }
}
