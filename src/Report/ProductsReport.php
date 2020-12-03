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

use App\Entity\Product;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTableBuilder;
use App\Util\FormatUtils;

/**
 * Report for the list of products.
 *
 * @author Laurent Muller
 */
class ProductsReport extends AbstractArrayReport
{
    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        //title
        $this->setTitleTrans('product.list.title');

        // new page
        $this->AddPage();

        // create table
        $table = $this->createTable();

        /** @var Product $entity */
        foreach ($entities as $entity) {
            // group
            $key = \sprintf('%s - %s', $entity->getParentCode(), $entity->getCategoryCode());
            $table->setGroupKey($key);

            // product
            $table->startRow()
                ->add($entity->getDescription())
                ->add($entity->getSupplier())
                ->add($entity->getUnit())
                ->add(FormatUtils::formatAmount($entity->getPrice()))
                ->endRow();
        }

        // count
        return $this->renderCount(\count($entities));
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
